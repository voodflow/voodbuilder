<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Conditions;

use Voodflow\Voodbuilder\Contracts\RegistersConditions;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsConditionHooks;

final class ConditionsModule extends AbstractVoodBuilderModule implements RegistersConditions
{
    public const ID = 'conditions';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Conditions';
    }

    public function capabilities(): array
    {
        return ['editor.conditions'];
    }

    public function conditionDefinitions(ModuleContext $context): array
    {
        return GrapesJsConditionHooks::options();
    }

    public static function isEnabled(): bool
    {
        $registry = app(ModuleRegistry::class);

        return $registry->has(self::ID) && $registry->isEnabled(self::ID);
    }
}
