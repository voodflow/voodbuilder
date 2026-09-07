<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing\Contracts;

use Voodflow\Voodbuilder\Licensing\CapabilitySet;
use Voodflow\Voodbuilder\Licensing\LicenceStatus;

/**
 * Entitlement Provider contract.
 */
interface EntitlementProvider
{
    public function capabilities(): CapabilitySet;

    public function licenceStatus(): LicenceStatus;
}
