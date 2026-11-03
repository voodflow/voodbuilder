<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SiteLanguageSwitcher;
use Voodflow\Voodbuilder\Support\SiteLocales;

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

        app()->setLocale($this->resolveLocale($request));

        return $next($request);
    }

    /**
     * `?locale=` wins, then the remembered visitor choice, then the site default.
     *
     * Every installed switcher keeps a session key ({@see SiteLanguageSwitcher::sessionKeys()}),
     * so the choice is written to all of them and no package owns the resolution.
     */
    protected function resolveLocale(Request $request): string
    {
        $keys = SiteLanguageSwitcher::sessionKeys();

        $queryLocale = $request->query(SiteLanguageSwitcher::QUERY_KEY);

        if (is_string($queryLocale) && SiteLocales::isValid($queryLocale)) {
            $this->remember($request, $keys, $queryLocale);

            return $queryLocale;
        }

        foreach ($keys as $key) {
            $sessionLocale = $request->session()->get($key);

            if (is_string($sessionLocale) && SiteLocales::isValid($sessionLocale)) {
                return $sessionLocale;
            }
        }

        return VoodbuilderSettings::primaryLocale();
    }

    /**
     * @param  list<string>  $keys
     */
    protected function remember(Request $request, array $keys, string $locale): void
    {
        foreach ($keys as $key) {
            if ($locale === SiteLocales::default()) {
                $request->session()->forget($key);
            } else {
                $request->session()->put($key, $locale);
            }
        }
    }
}
