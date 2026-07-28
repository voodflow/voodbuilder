<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Voodflow\Voodbuilder\Modules\ModuleContext;

interface RegistersEditorCommands
{
    /**
     * @return list<array{id: string, label?: string, meta?: array<string, mixed>}>
     */
    public function editorCommands(ModuleContext $context): array;
}
