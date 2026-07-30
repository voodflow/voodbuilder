<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Modules\ModuleContext;

/**
 * Module capability: register CSS/JS or Filament assets.
 */
interface RegistersAssets
{
    public function registerAssets(ModuleContext $context): void;
}
