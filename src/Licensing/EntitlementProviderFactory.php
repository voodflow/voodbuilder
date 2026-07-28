<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackEntitlementProvider;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackLicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

final class EntitlementProviderFactory
{
    public static function make(): EntitlementProvider
    {
        $driver = strtolower((string) config('voodbuilder.license.driver', 'config'));

        $provider = match ($driver) {
            'anystack' => self::anyStack(),
            'testing' => new TestingEntitlementProvider(
                EditionCapabilityMatrix::forEdition(
                    (string) config('voodbuilder.license.edition', EditionCapabilityMatrix::EDITION_COMMUNITY),
                ),
            ),
            default => new ConfigEntitlementProvider,
        };

        if ((bool) config('voodbuilder.license.cache', true) && ! $provider instanceof AnyStackEntitlementProvider) {
            return new CachedEntitlementProvider(
                $provider,
                (int) config('voodbuilder.license.cache_ttl', 3600),
            );
        }

        return $provider;
    }

    private static function anyStack(): EntitlementProvider
    {
        $key = trim((string) config('voodbuilder.license.key', ''));

        if ($key === '') {
            return new ConfigEntitlementProvider;
        }

        return new AnyStackEntitlementProvider(
            client: new AnyStackLicenceClient(
                endpoint: (string) config('voodbuilder.license.anystack.endpoint', ''),
                timeoutSeconds: (int) config('voodbuilder.license.anystack.timeout', 5),
            ),
            licenceKey: $key,
            graceSeconds: (int) config('voodbuilder.license.anystack.grace_seconds', 604800),
        );
    }
}
