<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Language switcher for the content translations VoodBuilder owns.
 *
 * A site with two languages in `APP_LOCALES` and translated pages gets a switcher
 * even when no other package is installed. This one is deliberately page-only: a
 * translated page links straight to its sibling, everything else keeps the current
 * URL and switches the visitor locale with `?locale=`, letting `SitePageResolver`
 * serve the translation (or fall back to the default language).
 *
 * A package that owns richer locale routing replaces it through
 * {@see LanguageSwitcherRegistry} — VoodBuilder never names that package.
 */
final class SiteLanguageSwitcher
{
    public const QUERY_KEY = 'locale';

    public const SESSION_LOCALE_KEY = 'voodbuilder.locale';

    public static function enabled(): bool
    {
        return SitePageResolver::localizationEnabled()
            && count(SiteLocales::codes()) > 1;
    }

    /**
     * Respects Admin → Settings → Header nav → language switcher.
     */
    public static function visible(): bool
    {
        return self::enabled()
            && (bool) VoodbuilderSettings::get('show_language_switcher', true);
    }

    /** @return array<string, string> locale code => label */
    public static function options(): array
    {
        return SiteLocales::options();
    }

    public static function current(): string
    {
        return SitePageResolver::preferredLocale();
    }

    public static function isCurrent(string $locale): bool
    {
        return $locale === self::current();
    }

    /**
     * A language is only offered when the visitor has somewhere to land: the home
     * needs its own home page, a content page needs its sibling translation. Other
     * routes can switch freely through `?locale=` (no landing redirect involved).
     */
    public static function isAvailable(string $locale): bool
    {
        if (! SiteLocales::isValid($locale)) {
            return false;
        }

        $page = self::currentPage();

        if ($page === null) {
            return true;
        }

        if ($page->is_home) {
            return SitePage::homePage($locale) !== null;
        }

        return $page->translationFor($locale) !== null;
    }

    /**
     * Session keys holding the visitor language choice.
     *
     * Every installed switcher keeps its own key; `config('voodbuilder.localization
     * .session_keys')` lets companions add theirs so the two stay in sync.
     *
     * @return list<string>
     */
    public static function sessionKeys(): array
    {
        $configured = (array) config('voodbuilder.localization.session_keys', []);

        $keys = array_values(array_filter(
            $configured,
            static fn (mixed $key): bool => is_string($key) && $key !== '',
        ));

        $keys[] = self::SESSION_LOCALE_KEY;

        return array_values(array_unique($keys));
    }

    /**
     * Drops the visitor language choice for every installed switcher.
     */
    public static function forgetVisitorLocale(): void
    {
        foreach (self::sessionKeys() as $key) {
            request()->session()->forget($key);
        }
    }

    /**
     * Same page in `$locale` when a sibling translation exists, otherwise the current
     * URL with `?locale=` so the visitor keeps their place while the site switches.
     */
    public static function urlFor(string $locale): string
    {
        if (! SiteLocales::isValid($locale)) {
            return request()->url();
        }

        $page = self::currentPage();

        if ($page !== null) {
            $translation = $page->translationFor($locale);

            if ($translation instanceof SitePage) {
                return $translation->getUrl();
            }

            if ($page->is_home) {
                // No home in that language: staying put is the only safe target, a
                // `?locale=` landing would bounce right back to the default home.
                return request()->url();
            }
        }

        return self::withLocaleQuery(request()->url(), $locale);
    }

    /**
     * Page currently being displayed, when the request is a VoodBuilder page route.
     */
    public static function currentPage(): ?SitePage
    {
        if (! Route::has('home') && ! Route::has('voodbuilder.pages.show')) {
            return null;
        }

        if (Route::is('home', 'home.localized')) {
            return SitePage::homePage(self::current());
        }

        if (! Route::is('voodbuilder.pages.show', 'voodbuilder.pages.show.nested')) {
            return null;
        }

        $slug = request()->route('slug');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        try {
            return SitePageResolver::publishedFromSlug($slug);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function withLocaleQuery(string $url, string $locale, array $query = []): string
    {
        $query = array_filter(
            array_merge($query, [self::QUERY_KEY => $locale]),
            static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== false,
        );

        $parts = parse_url($url);
        $parts = is_array($parts) ? $parts : [];

        $path = (string) ($parts['path'] ?? '/');
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = (string) ($parts['host'] ?? '');
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        $existing = [];

        if (isset($parts['query'])) {
            parse_str((string) $parts['query'], $existing);
        }

        $query = array_merge($existing, $query);

        $queryString = http_build_query($query);

        return $scheme . $host . $port . $path . ($queryString !== '' ? '?' . $queryString : '') . $fragment;
    }

    /**
     * Append (or replace) the locale query parameter on an absolute URL.
     *
     * @param  array<string, mixed>  $query
     */
    public static function appendLocaleQuery(string $url, string $locale, array $query = []): string
    {
        return self::withLocaleQuery($url, $locale, $query);
    }
}
