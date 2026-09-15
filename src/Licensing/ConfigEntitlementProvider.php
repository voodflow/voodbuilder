<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

/**
 * Resolves capabilities from config edition (community|professional|developer|agency).
 * No remote calls — AnyStack sits behind the same EntitlementProvider contract.
 */
final class ConfigEntitlementProvider implements EntitlementProvider
{
    public function capabilities(): CapabilitySet
    {
        $edition = (string) config('voodbuilder.license.edition', EditionCapabilityMatrix::EDITION_COMMUNITY);

        return CapabilitySet::from(EditionCapabilityMatrix::forEdition($edition));
    }

    public function licenceStatus(): LicenceStatus
    {
        $edition = EditionCapabilityMatrix::normalizeEdition((string) config(
            'voodbuilder.license.edition',
            EditionCapabilityMatrix::EDITION_COMMUNITY,
        ));

        return new LicenceStatus(
            edition: $edition,
            active: true,
            identifier: (string) config('voodbuilder.license.key', $edition) ?: $edition,
            expiresAt: null,
            message: null,
        );
    }
}
