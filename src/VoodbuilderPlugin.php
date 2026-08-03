<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Voodflow\Voodbuilder\Filament\Livewire\AdminDatabaseNotifications;
use Voodflow\Voodbuilder\Filament\Pages\VoodbuilderSettingsPage;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Filament\Resources\MediaLibraryResource;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;

/**
 * Voodbuilder Plugin.
 */
class VoodbuilderPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'voodbuilder';
    }

    public function register(Panel $panel): void
    {
        $resources = [];

        if (config('voodbuilder.modules.menus.enabled', true)) {
            $resources[] = NavigationMenuResource::class;
        }

        // Model Integrations live in voodflow/voodbuilder-dynamic-data (Filament plugin).

        if (config('voodbuilder.modules.pages.enabled', true) && config('voodbuilder.pages.enabled', true)) {
            $resources[] = SitePageResource::class;
        }

        // PopupResource is registered by VpopupsPlugin (voodflow/vpopups).

        if (config('voodbuilder.modules.layouts.enabled', true) && config('voodbuilder.chrome_layouts.enabled', true)) {
            $resources[] = ChromeLayoutResource::class;
        }

        // Prefer voodflow/vmedia when its Filament plugin is active.
        if (MediaLibraryResource::canAccess() && ! self::mediaCompanionActive()) {
            $resources[] = MediaLibraryResource::class;
        }

        $panel
            ->resources($resources)
            ->pages([
                VoodbuilderSettingsPage::class,
            ])
            ->databaseNotifications(livewireComponent: AdminDatabaseNotifications::class);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected static function mediaCompanionActive(): bool
    {
        $class = \Voodflow\Vmedia\Vmedia::class;

        return class_exists($class)
            && method_exists($class, 'isActive')
            && (bool) $class::isActive();
    }
}
