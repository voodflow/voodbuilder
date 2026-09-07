<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Conditions;

use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;

/**
 * Normalize locale-prefixed route names for editor conditions and visibility rules.
 *
 * Companions that publish localized routes name them `<package>.<locale>.<action>`, while
 * authors pick conditions against the logical name `<package>.<action>`. Providers
 * registered on the DynamicPageRegistry normalize their own names; this class also handles
 * installs where no provider is registered for a route the author already saved.
 */
final class LogicalRouteName
{
    public static function normalize(string $routeName): string
    {
        if ($routeName === '') {
            return '';
        }

        $normalized = $routeName;

        if (class_exists(DynamicPageRegistry::class)) {
            foreach (app(DynamicPageRegistry::class)->all() as $provider) {
                $normalized = $provider->normalizeRouteName($normalized);
            }
        }

        return self::stripLocaleSegments($normalized);
    }

    /**
     * Drop segments that are locales, leaving the rest of the name untouched.
     *
     * The previous implementation matched `vevents.<anything>.<rest>` and dropped the
     * middle segment wholesale. That hardcoded one companion's prefix into the core and,
     * worse, mangled names that carried no locale at all: `vevents.exhibitors.show`
     * normalized to `vevents.show`, so a condition an author had set on the logical name
     * never matched the request.
     */
    private static function stripLocaleSegments(string $routeName): string
    {
        $segments = explode('.', $routeName);

        if (count($segments) < 3) {
            return $routeName;
        }

        $locales = self::knownLocales();

        // Never strip the package prefix or the trailing action, only what sits between.
        $kept = [$segments[0]];

        for ($index = 1; $index < count($segments) - 1; $index++) {
            if (! self::isLocaleSegment($segments[$index], $locales)) {
                $kept[] = $segments[$index];
            }
        }

        $kept[] = $segments[count($segments) - 1];

        return implode('.', $kept);
    }

    /**
     * @param  list<string>  $locales
     */
    private static function isLocaleSegment(string $segment, array $locales): bool
    {
        if ($segment === '') {
            return false;
        }

        if (in_array(strtolower($segment), $locales, true)) {
            return true;
        }

        // Fall back to the shape of a language tag for installs that never declared their
        // locale list. Kept strict on purpose: a longer segment is a route noun, not a
        // locale, which is exactly the case the old regex got wrong.
        return preg_match('/^[a-z]{2}([-_][a-z]{2,4})?$/i', $segment) === 1;
    }

    /**
     * @return list<string>
     */
    private static function knownLocales(): array
    {
        $locales = [];

        foreach (['voodbuilder.editor.conditions.locales', 'app.locales', 'vtuts.locales', 'vdocs.locales'] as $key) {
            $configured = config($key);

            if (! is_array($configured)) {
                continue;
            }

            foreach ($configured as $configuredKey => $value) {
                foreach ([$configuredKey, $value] as $candidate) {
                    if (is_string($candidate) && $candidate !== '') {
                        $locales[] = strtolower($candidate);
                    }
                }
            }
        }

        $locales[] = strtolower(app()->getLocale());
        $locales[] = strtolower((string) config('app.fallback_locale', 'en'));

        return array_values(array_unique($locales));
    }
}
