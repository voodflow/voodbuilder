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
 *
 * Never throws to callers. Outages fail open on the last successful snapshot so a
 * billing outage cannot strip authoring mid-project. A deliberate inactive reply from
 * AnyStack still downgrades authoring to Community; published pages never consult this
 * provider for content rendering (see DynamicDataCollectionsBridge::renderingEnabled()).
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
     *     catalog_credentials: ?array<string, string>,
     *     fetched_at: int,
     * }
     */
    private function resolveSnapshot(): array
    {
        try {
            $fresh = $this->client->fetchEntitlements($this->licenceKey);
            $snapshot = $this->normalizeFreshSnapshot($fresh);
            Cache::forever(self::SNAPSHOT_CACHE_KEY, $snapshot);

            return $snapshot;
        } catch (LicenceClientException) {
            return $this->outageSnapshot();
        }
    }

    /**
     * @param  array{
     *     edition: string,
     *     active: bool,
     *     capabilities: list<string>,
     *     identifier?: ?string,
     *     expires_at?: ?string,
     *     message?: ?string,
     *     catalog_credentials?: ?array<string, string>,
     * }  $fresh
     * @return array{
     *     edition: string,
     *     active: bool,
     *     capabilities: list<string>,
     *     identifier: ?string,
     *     expires_at: ?string,
     *     message: ?string,
     *     catalog_credentials: ?array<string, string>,
     *     fetched_at: int,
     * }
     */
    private function normalizeFreshSnapshot(array $fresh): array
    {
        $active = (bool) ($fresh['active'] ?? true);
        $edition = (string) $fresh['edition'];
        $capabilities = array_values(array_map('strval', $fresh['capabilities']));
        $message = isset($fresh['message']) ? (string) $fresh['message'] : null;

        // Legitimate non-renewal: AnyStack answered. Downgrade authoring only — never touch
        // published rendering, which does not read this snapshot for content.
        if (! $active) {
            $edition = EditionCapabilityMatrix::EDITION_COMMUNITY;
            $capabilities = EditionCapabilityMatrix::community();
            $message = $message ?: (string) __('voodbuilder::license.expired');
        }

        return [
            'edition' => $edition,
            'active' => $active,
            'capabilities' => $capabilities,
            'identifier' => $fresh['identifier'] ?? $this->licenceKey,
            'expires_at' => $fresh['expires_at'] ?? null,
            'message' => $message,
            'catalog_credentials' => is_array($fresh['catalog_credentials'] ?? null)
                ? $fresh['catalog_credentials']
                : null,
            'fetched_at' => time(),
        ];
    }

    /**
     * @return array{
     *     edition: string,
     *     active: bool,
     *     capabilities: list<string>,
     *     identifier: ?string,
     *     expires_at: ?string,
     *     message: ?string,
     *     catalog_credentials: ?array<string, string>,
     *     fetched_at: int,
     * }
     */
    private function outageSnapshot(): array
    {
        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get(self::SNAPSHOT_CACHE_KEY);

        if (is_array($cached) && isset($cached['fetched_at'], $cached['capabilities'], $cached['edition'])) {
            $age = time() - (int) $cached['fetched_at'];
            $withinGrace = $age <= $this->graceSeconds;

            return [
                'edition' => (string) $cached['edition'],
                'active' => (bool) ($cached['active'] ?? true),
                'capabilities' => array_values(array_map('strval', (array) $cached['capabilities'])),
                'identifier' => isset($cached['identifier']) ? (string) $cached['identifier'] : null,
                'expires_at' => isset($cached['expires_at']) ? (string) $cached['expires_at'] : null,
                'message' => (string) ($withinGrace
                    ? ($cached['message'] ?? __('voodbuilder::license.grace_active'))
                    : __('voodbuilder::license.stale_cache_fail_open')),
                'catalog_credentials' => is_array($cached['catalog_credentials'] ?? null)
                    ? array_map('strval', $cached['catalog_credentials'])
                    : null,
                'fetched_at' => (int) $cached['fetched_at'],
            ];
        }

        // No prior success: Community authoring only. Public pages still render stored
        // content without consulting capabilities.
        return [
            'edition' => EditionCapabilityMatrix::EDITION_COMMUNITY,
            'active' => true,
            'capabilities' => EditionCapabilityMatrix::community(),
            'identifier' => $this->licenceKey !== '' ? $this->licenceKey : 'community-fallback',
            'expires_at' => null,
            'message' => (string) __('voodbuilder::license.remote_unavailable'),
            'catalog_credentials' => null,
            'fetched_at' => time(),
        ];
    }
}
