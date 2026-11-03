<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Support\Consent;

/**
 * Visitor consent, supplied by whichever consent package the host installs.
 *
 * VoodBuilder asks “may I run this?” — it never names the package that answers.
 * Implementations are registered through {@see Consent}.
 */
interface ConsentGate
{
    /**
     * @param  string  $category  e.g. `marketing`, `analytics`, `necessary`
     */
    public function allows(string $category): bool;
}
