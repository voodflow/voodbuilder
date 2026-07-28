<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules;

use Voodflow\Voodbuilder\Contracts\VoodBuilderModule;

/**
 * Convenience base for internal modules. Prefer small contributor interfaces over growing this class.
 */
abstract class AbstractVoodBuilderModule implements VoodBuilderModule
{
    public function version(): string
    {
        return '0.1.0-dev';
    }

    public function dependencies(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return [];
    }

    public function register(ModuleContext $context): void
    {
        //
    }

    public function boot(ModuleContext $context): void
    {
        //
    }
}
