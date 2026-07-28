<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing\AnyStack;

use Illuminate\Support\Facades\Cache;
use Voodflow\Voodbuilder\Licensing\CapabilitySet;
use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClientException;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\LicenceStatus;

/**
 * Fetches remote entitlements and falls back to a local snapshot during outages.
 * Never throws to callers — live sites keep last-known capabilities within grace.
 */
final class AnyStackEntitlementProvider implements EntitlementProvider
{
    public const SNAPSHOT_CACHE_KEY = 'voodbuilder.entitlements.anystack.snapshot';

    public function __construct(
        private readonly LicenceClient $client,
        private readonly string $licenceKey,
        private readonly int $graceSeconds = 604800,
    ) {}

    public function capabilities(): CapabilitySet
    {
        return CapabilitySet::from($this->resolveSnapshot()['capabilities']);
    }

    public function licenceStatus(): LicenceStatus
    {
        $snapshot = $this->resolveSnapshot();

        return new LicenceStatus(
            edition: $snapshot['edition'],
            active: $snapshot['active'],
            identifier: $snapshot['identifier'],
            expiresAt: $snapshot['expires_at'],
            message: $snapshot['message'],
        );
    }

    /**
     * @return array{
     *     edition: string,
     *     active: bool,
     *     capabilities: list<string>,
     *     identifier: ?string,
     *     expires_at: ?string,
     *     message: ?string,
     *     fetched_at: int,
     * }
     */
    private function resolveSnapshot(): array
    {
        try {
            $fresh = $this->client->fetchEntitlements($this->licenceKey);
            $snapshot = [
                'edition' => $fresh['edition'],
                'active' => $fresh['active'],
                'capabilities' => $fresh['capabilities'],
                'identifier' => $fresh['identifier'] ?? $this->licenceKey,
                'expires_at' => $fresh['expires_at'] ?? null,
                'message' => $fresh['message'] ?? null,
                'fetched_at' => time(),
            ];
            Cache::forever(self::SNAPSHOT_CACHE_KEY, $snapshot);

            return $snapshot;
        } catch (LicenceClientException) {
            return $this->graceSnapshot();
        }
    }

    /**
     * @return array{
     *     edition: string,
     *     active: bool,
     *     capabilities: list<string>,
     *     identifier: ?string,
     *     expires_at: ?string,
     *     message: ?string,
     *     fetched_at: int,
     * }
     */
    private function graceSnapshot(): array
    {
        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get(self::SNAPSHOT_CACHE_KEY);

        if (is_array($cached) && isset($cached['fetched_at'], $cached['capabilities'], $cached['edition'])) {
            $age = time() - (int) $cached['fetched_at'];

            if ($age <= $this->graceSeconds) {
                return [
                    'edition' => (string) $cached['edition'],
                    'active' => (bool) ($cached['active'] ?? true),
                    'capabilities' => array_values(array_map('strval', (array) $cached['capabilities'])),
                    'identifier' => isset($cached['identifier']) ? (string) $cached['identifier'] : null,
                    'expires_at' => isset($cached['expires_at']) ? (string) $cached['expires_at'] : null,
                    'message' => (string) ($cached['message'] ?? __('voodbuilder::license.grace_active')),
                    'fetched_at' => (int) $cached['fetched_at'],
                ];
            }
        }

        // Soft fail: Community capabilities only — never break public rendering.
        return [
            'edition' => EditionCapabilityMatrix::EDITION_COMMUNITY,
            'active' => true,
            'capabilities' => EditionCapabilityMatrix::community(),
            'identifier' => $this->licenceKey !== '' ? $this->licenceKey : 'community-fallback',
            'expires_at' => null,
            'message' => (string) __('voodbuilder::license.remote_unavailable'),
            'fetched_at' => time(),
        ];
    }
}
