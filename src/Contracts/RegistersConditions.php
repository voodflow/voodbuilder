<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Modules\ModuleContext;

/**
 * Module capability: register visibility condition evaluators.
 */
interface RegistersConditions
{
    /**
     * @return list<array{id: string, label: string, meta?: array<string, mixed>}>
     */
    public function conditionDefinitions(ModuleContext $context): array;
}
