<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackEntitlementProvider;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackLicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

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

        if ($key === '') {
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
            graceSeconds: (int) config('voodbuilder.license.grace_seconds', 604800),
        );
    }
}
