<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Backend enforcement helper — frontend hiding alone is insufficient (§11.5).
 */
final class EntitlementGate
{
    public static function authorize(string $capability): void
    {
        abort_unless(
            Voodbuilder::can($capability),
            403,
            "Missing VoodBuilder capability [{$capability}].",
        );
    }
}
