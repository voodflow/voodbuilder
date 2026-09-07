<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Modules\ModuleContext;

/**
 * Contract for a bootable VoodBuilder module (core internal or companion package).
 */
interface VoodBuilderModule
{
    public function id(): string;

    public function name(): string;

    public function version(): string;

    /**
     * @return list<string>
     */
    public function dependencies(): array;

    /**
     * Capability identifiers contributed or required by this module.
     *
     * @return list<string>
     */
    public function capabilities(): array;

    public function register(ModuleContext $context): void;

    public function boot(ModuleContext $context): void;
}
