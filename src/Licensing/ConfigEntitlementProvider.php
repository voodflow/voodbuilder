<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

/**
 * Resolves capabilities from config edition (community|professional|agency).
 * No remote calls — Phase 10 adds AnyStack behind this contract.
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
        $edition = strtolower(trim((string) config(
            'voodbuilder.license.edition',
            EditionCapabilityMatrix::EDITION_COMMUNITY,
        )));

        if (! in_array($edition, [
            EditionCapabilityMatrix::EDITION_COMMUNITY,
            EditionCapabilityMatrix::EDITION_PROFESSIONAL,
            EditionCapabilityMatrix::EDITION_AGENCY,
        ], true)) {
            $edition = EditionCapabilityMatrix::EDITION_COMMUNITY;
        }

        return new LicenceStatus(
            edition: $edition,
            active: true,
            identifier: (string) config('voodbuilder.license.key', $edition) ?: $edition,
            expiresAt: null,
            message: null,
        );
    }
}
