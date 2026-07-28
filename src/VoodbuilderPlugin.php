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
        $resources = [];

        if (config('voodbuilder.modules.menus.enabled', true)) {
            $resources[] = NavigationMenuResource::class;
        }

        // Single-record integrations are Community; collections are entitlement-gated in the editor.
        if (config('voodbuilder.modules.dynamic_data.enabled', true) && Voodbuilder::can('dynamic-data.single')) {
            $resources[] = ModelIntegrationResource::class;
        }

        if (config('voodbuilder.modules.pages.enabled', true) && config('voodbuilder.pages.enabled', true)) {
            $resources[] = SitePageResource::class;
        }

        if (config('voodbuilder.modules.popups.enabled', true)
            && config('voodbuilder.popups.enabled', true)
            && Voodbuilder::can('popups.builder')) {
            $resources[] = PopupResource::class;
        }

        if (config('voodbuilder.modules.layouts.enabled', true) && config('voodbuilder.chrome_layouts.enabled', true)) {
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
