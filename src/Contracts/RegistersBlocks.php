<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;

/**
 * Module capability: register editor sidebar / library blocks.
 */
interface RegistersBlocks
{
    public function registerBlocks(EditorBlockRegistry $blocks, ModuleContext $context): void;
}
