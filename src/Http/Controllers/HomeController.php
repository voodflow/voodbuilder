<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\SitePageViewData;
use Voodflow\Vpress\Support\VpressUrls;
use Voodflow\Vtuts\Support\Locales;

class HomeController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $locale = $this->resolvedHomeLocale();
        $page = SitePage::homePage($locale);

        if ($page === null && $locale !== null && class_exists(Locales::class) && $locale !== Locales::default()) {
            $defaultHome = SitePage::homePage(Locales::default());

            if ($defaultHome !== null) {
                return redirect(VpressUrls::home(Locales::default()));
            }
        }

        if ($page && ($page->usesGrapesJsBuilder() || filled($page->content))) {
            seo()->for($page);

            app()->setLocale($page->locale);

            return view('vpress::pages.site-page', SitePageViewData::make($page));
        }

        $fallbackTitle = config('vpress.home.fallback_seo.title')
            ?? VpressSettings::siteTitle();
        $fallbackDescription = config('vpress.home.fallback_seo.description')
            ?? VpressSettings::get('seo_default_description');

        if ($fallbackTitle || $fallbackDescription) {
            seo()->for(new SEOData(
                title: $fallbackTitle ?? VpressSettings::siteTitle(),
                description: $fallbackDescription,
            ));
        }

        return view(config('vpress.home.fallback_view', 'vpress::pages.welcome'));
    }

    protected function resolvedHomeLocale(): ?string
    {
        if (! class_exists(\Voodflow\Vtuts\Support\Locales::class)) {
            return null;
        }

        $queryLocale = request()->query('locale');

        if (is_string($queryLocale) && \Voodflow\Vtuts\Support\Locales::isValid($queryLocale)) {
            return $queryLocale;
        }

        $routeLocale = request()->route('locale');

        if (is_string($routeLocale) && \Voodflow\Vtuts\Support\Locales::isValid($routeLocale)) {
            return $routeLocale;
        }

        return \Voodflow\Vpress\Support\SitePageResolver::preferredLocale();
    }
}
