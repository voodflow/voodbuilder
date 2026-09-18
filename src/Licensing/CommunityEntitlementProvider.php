<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

/**
 * Fixed Community matrix when no remote licence is available.
 */
class CommunityEntitlementProvider implements EntitlementProvider
{
    public function capabilities(): CapabilitySet
    {
        return CapabilitySet::from(EditionCapabilityMatrix::community());
    }

    public function licenceStatus(): LicenceStatus
    {
        return new LicenceStatus(
            edition: EditionCapabilityMatrix::EDITION_COMMUNITY,
            active: true,
            identifier: 'community',
            expiresAt: null,
            message: null,
        );
    }
}
