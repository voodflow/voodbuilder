<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Vtuts\Support\Locales;

/**
 * Site-configured default locale helpers.
 *
 * Do not use runtime app()->getLocale() / config('app.locale') as the site default:
 * request middleware may temporarily set those to the active visitor locale.
 */
final class SiteLocales
{
    public static function default(): string
    {
        if (! class_exists(Locales::class)) {
            return self::fallbackWithoutVtuts();
        }

        foreach ([
            config('voodbuilder.default_locale'),
            config('vtuts.default_locale'),
            config('app.fallback_locale'),
        ] as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);

            if ($candidate !== '' && Locales::isValid($candidate)) {
                return $candidate;
            }
        }

        $codes = Locales::codes();

        return $codes[0] ?? 'en';
    }

    public static function isValid(string $locale): bool
    {
        if (class_exists(Locales::class)) {
            return Locales::isValid($locale);
        }

        return $locale !== '';
    }

    protected static function fallbackWithoutVtuts(): string
    {
        foreach ([
            config('voodbuilder.default_locale'),
            config('app.fallback_locale'),
            'en',
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return 'en';
    }
}
