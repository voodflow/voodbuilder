<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Themes;

use Livewire\Livewire;
use Voodflow\Voodbuilder\Contracts\RegistersAssets;
use Voodflow\Voodbuilder\Filament\Livewire\ThemeMapBridge;
use Voodflow\Voodbuilder\Filament\Livewire\ThemesWorkspace;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\ThemeMapAssets;

/**
 * Theme Studio UI (workspace + theme map). Built-in theme resolution stays in Core.
 */
final class ThemesModule extends AbstractVoodBuilderModule implements RegistersAssets
{
    public const ID = 'themes';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Themes';
    }

    public function capabilities(): array
    {
        return [
            'themes.studio',
            'themes.clone',
            'themes.map',
        ];
    }

    public function registerAssets(ModuleContext $context): void
    {
        Livewire::component('voodbuilder.themes-workspace', ThemesWorkspace::class);
        Livewire::component('voodbuilder.theme-map-bridge', ThemeMapBridge::class);
        ThemeMapAssets::register();
    }

    public function register(ModuleContext $context): void
    {
        $this->registerAssets($context);
    }

    public static function isEnabled(): bool
    {
        $registry = app(ModuleRegistry::class);

        return $registry->has(self::ID) && $registry->isEnabled(self::ID);
    }
}
