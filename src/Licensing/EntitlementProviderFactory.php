<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackEntitlementProvider;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackLicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClientException;

/**
 * Builds the entitlement provider used at runtime.
 */
final class EntitlementProviderFactory
{
    public static function make(): EntitlementProvider
    {
        $driver = strtolower((string) config('voodbuilder.license.driver', 'anystack'));

        if ($driver === 'testing') {
            $provider = new TestingEntitlementProvider(
                EditionCapabilityMatrix::forEdition(
                    (string) config(
                        'voodbuilder.license.testing_edition',
                        EditionCapabilityMatrix::EDITION_AGENCY,
                    ),
                ),
            );
        } else {
            $provider = self::remoteOrCommunity();
        }

        if ((bool) config('voodbuilder.license.cache', true) && ! $provider instanceof AnyStackEntitlementProvider) {
            return new CachedEntitlementProvider(
                $provider,
                (int) config('voodbuilder.license.cache_ttl', 3600),
            );
        }

        return $provider;
    }

    private static function remoteOrCommunity(): EntitlementProvider
    {
        $key = LicenceKeyResolver::resolve();
        $graceSeconds = (int) config('voodbuilder.license.grace_seconds', 604800);

        if ($key === '') {
            // Key temporarily unreadable (auth.json / COMPOSER_AUTH) must not wipe a paid
            // snapshot — fail open on the last AnyStack success when present.
            if (AnyStackEntitlementProvider::hasCachedSnapshot()) {
                return new AnyStackEntitlementProvider(
                    client: self::unreachableClient('Licence key not configured'),
                    licenceKey: '',
                    graceSeconds: $graceSeconds,
                );
            }

            return new CommunityEntitlementProvider;
        }

        return new AnyStackEntitlementProvider(
            client: new AnyStackLicenceClient(
                endpoint: (string) config(
                    'voodbuilder.license.endpoint',
                    'https://api.voodflow.com/v1/packages/voodbuilder',
                ),
                timeoutSeconds: (int) config('voodbuilder.license.timeout', 5),
            ),
            licenceKey: $key,
            graceSeconds: $graceSeconds,
        );
    }

    private static function unreachableClient(string $message): LicenceClient
    {
        return new class($message) implements LicenceClient
        {
            public function __construct(private readonly string $message) {}

            public function fetchEntitlements(string $licenceKey): array
            {
                throw LicenceClientException::unreachable($this->message);
            }
        };
    }
}
