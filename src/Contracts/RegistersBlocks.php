<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;

interface RegistersBlocks
{
    public function registerBlocks(GrapesJsBlockRegistry $blocks, ModuleContext $context): void;
}
