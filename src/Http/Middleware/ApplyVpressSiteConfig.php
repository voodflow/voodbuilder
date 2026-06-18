<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vtuts\Support\Locales;
use Voodflow\Vtuts\Support\LocaleSwitcher;

class ApplyVpressSiteConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'seo.canonical_link' => (bool) VpressSettings::get('seo_canonical_enabled', true),
        ]);

        if (class_exists(Locales::class)) {
            app()->setLocale($this->resolveLocale($request));
        }

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $queryLocale = $request->query('locale');

        if (is_string($queryLocale) && Locales::isValid($queryLocale)) {
            if ($queryLocale === Locales::default()) {
                $request->session()->forget(LocaleSwitcher::SESSION_LOCALE_KEY);
            } else {
                $request->session()->put(LocaleSwitcher::SESSION_LOCALE_KEY, $queryLocale);
            }

            return $queryLocale;
        }

        $sessionLocale = $request->session()->get(LocaleSwitcher::SESSION_LOCALE_KEY);

        if (is_string($sessionLocale) && Locales::isValid($sessionLocale)) {
            return $sessionLocale;
        }

        return VpressSettings::primaryLocale();
    }
}
