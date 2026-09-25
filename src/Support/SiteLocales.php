<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Locale;

/**
 * Site languages, defined once by the host app (`APP_LOCALES` / `APP_LOCALE` in .env,
 * exposed as `config('app.locales')` and `config('app.default_locale')`).
 *
 * Packages never define their own language list. Do not use runtime app()->getLocale() /
 * config('app.locale') as the site default: request middleware sets those to the visitor locale.
 */
final class SiteLocales
{
    /**
     * @return array<string, string> locale code => label
     */
    public static function options(): array
    {
        $configured = self::normalize(config('app.locales'));

        if ($configured !== []) {
            return $configured;
        }

        $fallback = self::configuredDefault() ?? 'en';

        return [$fallback => self::labelFor($fallback)];
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::options());
    }

    public static function default(): string
    {
        $codes = self::codes();
        $configured = self::configuredDefault();

        return $configured !== null && in_array($configured, $codes, true)
            ? $configured
            : $codes[0];
    }

    public static function isValid(string $locale): bool
    {
        return in_array($locale, self::codes(), true);
    }

    /** @return list<string> */
    public static function nonDefaultCodes(): array
    {
        $default = self::default();

        return array_values(array_filter(
            self::codes(),
            fn (string $code): bool => $code !== $default,
        ));
    }

    public static function usesUrlPrefix(): bool
    {
        return (bool) config('vtuts.localization.prefix_non_default_locale', false);
    }

    public static function labelFor(string $locale): string
    {
        if (class_exists(Locale::class)) {
            $label = Locale::getDisplayLanguage($locale, $locale);

            if (is_string($label) && $label !== '' && $label !== $locale) {
                return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return strtoupper($locale);
    }

    private static function configuredDefault(): ?string
    {
        foreach ([config('app.default_locale'), config('app.fallback_locale')] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * Accepts `en,it`, `['en', 'it']` or `['en' => 'English', 'it' => 'Italiano']`.
     *
     * @return array<string, string>
     */
    private static function normalize(mixed $configured): array
    {
        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        if (! is_array($configured)) {
            return [];
        }

        $locales = [];

        foreach ($configured as $code => $label) {
            if (is_int($code) && is_string($label) && trim($label) !== '') {
                $locales[trim($label)] = self::labelFor(trim($label));

                continue;
            }

            if (is_string($code) && trim($code) !== '' && is_string($label) && $label !== '') {
                $locales[trim($code)] = $label;
            }
        }

        return $locales;
    }
}
