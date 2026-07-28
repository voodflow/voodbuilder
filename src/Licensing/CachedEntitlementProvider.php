<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Illuminate\Support\Facades\Cache;
use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

/**
 * Caches capability snapshots locally. Public render never depends on this failing.
 */
final class CachedEntitlementProvider implements EntitlementProvider
{
    public function __construct(
        private readonly EntitlementProvider $inner,
        private readonly int $ttlSeconds = 3600,
    ) {}

    public function capabilities(): CapabilitySet
    {
        try {
            /** @var list<string> $caps */
            $caps = Cache::remember($this->cacheKey('capabilities'), $this->ttlSeconds, function (): array {
                return $this->inner->capabilities()->all();
            });

            return CapabilitySet::from($caps);
        } catch (\Throwable) {
            return $this->inner->capabilities();
        }
    }

    public function licenceStatus(): LicenceStatus
    {
        try {
            /** @var array{edition: string, active: bool, identifier: ?string, expiresAt: ?string, message: ?string} $payload */
            $payload = Cache::remember($this->cacheKey('status'), $this->ttlSeconds, function (): array {
                $status = $this->inner->licenceStatus();

                return [
                    'edition' => $status->edition,
                    'active' => $status->active,
                    'identifier' => $status->identifier,
                    'expiresAt' => $status->expiresAt,
                    'message' => $status->message,
                ];
            });

            return new LicenceStatus(
                edition: $payload['edition'],
                active: $payload['active'],
                identifier: $payload['identifier'],
                expiresAt: $payload['expiresAt'],
                message: $payload['message'],
            );
        } catch (\Throwable) {
            return $this->inner->licenceStatus();
        }
    }

    public function forget(): void
    {
        try {
            Cache::forget($this->cacheKey('capabilities'));
            Cache::forget($this->cacheKey('status'));
        } catch (\Throwable) {
            //
        }
    }

    private function cacheKey(string $suffix): string
    {
        return 'voodbuilder.entitlements.'.$suffix;
    }
}
