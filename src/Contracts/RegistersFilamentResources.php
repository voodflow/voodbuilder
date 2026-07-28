<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Filament\Panel;
use Voodflow\Voodbuilder\Modules\ModuleContext;

interface RegistersFilamentResources
{
    public function registerFilamentResources(Panel $panel, ModuleContext $context): void;
}
