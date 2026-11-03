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
 * `voodflow/vtuts` ships its own switcher for tutorial content; a site that only
 * installs VoodBuilder still has two languages in `APP_LOCALES` and translated
 * pages, so the header needs a switcher for those too. This one is deliberately
 * page-only: a translated page links straight to its sibling, everything else keeps
 * the current URL and switches the visitor locale with `?locale=`, letting
 * `SitePageResolver` serve the translation (or fall back to the default language).
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

            if ($page->is_home && $locale !== self::current()) {
                $home = SitePage::homePage($locale);

                if ($home instanceof SitePage) {
                    return $home->getUrl();
                }
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
}
