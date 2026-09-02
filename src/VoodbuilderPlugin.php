<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Voodflow\Voodbuilder\Filament\Livewire\AdminDatabaseNotifications;
use Voodflow\Voodbuilder\Filament\Pages\ThemeStudioPage;
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

        // Registered on config alone. Whether it is reachable and whether it shows in the
        // sidebar is MediaLibraryResource::canAccess(), which also stands down for
        // voodflow/vmedia — that question cannot be answered here, because the companion
        // activates inside its own plugin's register() and the host lists it after ours.
        if (config('voodbuilder.media_library.enabled', true) && config('voodbuilder.modules.media_library.enabled', true)) {
            $resources[] = MediaLibraryResource::class;
        }

        $panel
            ->resources($resources)
            ->pages([
                ThemeStudioPage::class,
                VoodbuilderSettingsPage::class,
            ])
            ->databaseNotifications(livewireComponent: AdminDatabaseNotifications::class);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
