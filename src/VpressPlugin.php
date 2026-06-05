<?php

declare(strict_types=1);

namespace Voodflow\Vpress;

use Filament\Contracts\Plugin;
use Filament\Panel;
use JeffersonGoncalves\Filament\CookieConsent\CookieConsentPlugin;
use Voodflow\Vpress\Filament\Livewire\AdminDatabaseNotifications;
use Voodflow\Vpress\Filament\Pages\VpressSettingsPage;
use Voodflow\Vpress\Filament\Resources\NavigationMenuResource;
use Voodflow\Vpress\Filament\Resources\SitePageResource;

class VpressPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'vpress';
    }

    public function register(Panel $panel): void
    {
        $resources = [NavigationMenuResource::class];

        if (config('vpress.pages.enabled', true)) {
            $resources[] = SitePageResource::class;
        }

        $panel
            ->resources($resources)
            ->pages([
                VpressSettingsPage::class,
            ])
            ->databaseNotifications(livewireComponent: AdminDatabaseNotifications::class);

        CookieConsentPlugin::make()->register($panel);
    }

    public function boot(Panel $panel): void
    {
        CookieConsentPlugin::make()->boot($panel);
    }
}
