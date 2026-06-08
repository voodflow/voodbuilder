<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\Lang;

/**
 * vpress:install disables auto-discovery for filament-cookie-consent so the banner
 * is not injected into Filament panels. The Filament settings page is still registered
 * via VpressPlugin, so we must load package translations manually.
 */
final class RegisterFilamentCookieConsentTranslations
{
    public static function apply(): void
    {
        if (! class_exists(\JeffersonGoncalves\Filament\CookieConsent\CookieConsentPlugin::class)) {
            return;
        }

        $langPath = base_path('vendor/jeffersongoncalves/filament-cookie-consent/resources/lang');

        if (! is_dir($langPath)) {
            return;
        }

        Lang::addNamespace('filament-cookie-consent', $langPath);
    }
}
