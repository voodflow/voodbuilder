<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SiteLanguageSwitcher;
use Voodflow\Voodbuilder\Support\SiteLocales;
use Voodflow\Vtuts\Support\LocaleSwitcher;

/**
 * Apply Voodbuilder Site Config.
 */
class ApplyVoodbuilderSiteConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'seo.canonical_link' => (bool) VoodbuilderSettings::get('seo_canonical_enabled', true),
        ]);

        $sessionKey = $this->sessionKey();

        if ($sessionKey !== null) {
            app()->setLocale($this->resolveLocale($request, $sessionKey));
        }

        return $next($request);
    }

    /**
     * vtuts owns the locale session when it is installed; otherwise VoodBuilder's own
     * switcher does, so `?locale=` still works on a page-translation-only site.
     */
    protected function sessionKey(): ?string
    {
        if (class_exists(LocaleSwitcher::class)) {
            return LocaleSwitcher::SESSION_LOCALE_KEY;
        }

        return SiteLanguageSwitcher::enabled()
            ? SiteLanguageSwitcher::SESSION_LOCALE_KEY
            : null;
    }

    protected function resolveLocale(Request $request, string $sessionKey): string
    {
        $queryLocale = $request->query(SiteLanguageSwitcher::QUERY_KEY);

        if (is_string($queryLocale) && SiteLocales::isValid($queryLocale)) {
            if ($queryLocale === SiteLocales::default()) {
                $request->session()->forget($sessionKey);
            } else {
                $request->session()->put($sessionKey, $queryLocale);
            }

            return $queryLocale;
        }

        $sessionLocale = $request->session()->get($sessionKey);

        if (is_string($sessionLocale) && SiteLocales::isValid($sessionLocale)) {
            return $sessionLocale;
        }

        return VoodbuilderSettings::primaryLocale();
    }
}
