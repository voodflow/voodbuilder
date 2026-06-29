<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Lang;
use JeffersonGoncalves\Filament\CookieConsent\CookieConsentPlugin;

/**
 * voodbuilder:install disables auto-discovery for filament-cookie-consent so the banner
 * is not injected into Filament panels. The Filament settings page is still registered
 * via VoodbuilderPlugin, so we must load package translations manually.
 */
final class RegisterFilamentCookieConsentTranslations
{
    public static function apply(): void
    {
        if (! class_exists(CookieConsentPlugin::class)) {
            return;
        }

        $langPath = base_path('vendor/jeffersongoncalves/filament-cookie-consent/resources/lang');

        if (! is_dir($langPath)) {
            return;
        }

        Lang::addNamespace('filament-cookie-consent', $langPath);
    }
}
