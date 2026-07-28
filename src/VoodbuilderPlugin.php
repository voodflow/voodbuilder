<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use JeffersonGoncalves\Filament\CookieConsent\CookieConsentPlugin;
use Voodflow\Voodbuilder\Filament\Livewire\AdminDatabaseNotifications;
use Voodflow\Voodbuilder\Filament\Pages\VoodbuilderSettingsPage;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Filament\Resources\ModelIntegrationResource;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;

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
        $resources = [
            ModelIntegrationResource::class,
        ];

        if (config('voodbuilder.modules.menus.enabled', true)) {
            $resources[] = NavigationMenuResource::class;
        }

        if (config('voodbuilder.pages.enabled', true)) {
            $resources[] = SitePageResource::class;
        }

        if (config('voodbuilder.popups.enabled', true)) {
            $resources[] = PopupResource::class;
        }

        if (config('voodbuilder.chrome_layouts.enabled', true)) {
            $resources[] = ChromeLayoutResource::class;
        }

        $panel
            ->resources($resources)
            ->pages([
                VoodbuilderSettingsPage::class,
            ])
            ->databaseNotifications(livewireComponent: AdminDatabaseNotifications::class);

        CookieConsentPlugin::make()->register($panel);
    }

    public function boot(Panel $panel): void
    {
        CookieConsentPlugin::make()->boot($panel);
    }
}
