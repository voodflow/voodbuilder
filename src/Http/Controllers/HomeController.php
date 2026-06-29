<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SitePageViewData;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;
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
                return redirect(VoodbuilderUrls::home(Locales::default()));
            }
        }

        if ($page && ($page->usesGrapesJsBuilder() || filled($page->content))) {
            seo()->for($page);

            app()->setLocale($page->locale);

            return view('voodbuilder::pages.site-page', SitePageViewData::make($page));
        }

        $fallbackTitle = config('voodbuilder.home.fallback_seo.title')
            ?? VoodbuilderSettings::siteTitle();
        $fallbackDescription = config('voodbuilder.home.fallback_seo.description')
            ?? VoodbuilderSettings::get('seo_default_description');

        if ($fallbackTitle || $fallbackDescription) {
            seo()->for(new SEOData(
                title: $fallbackTitle ?? VoodbuilderSettings::siteTitle(),
                description: $fallbackDescription,
            ));
        }

        return view(config('voodbuilder.home.fallback_view', 'voodbuilder::pages.welcome'));
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

        return \Voodflow\Voodbuilder\Support\SitePageResolver::preferredLocale();
    }
}
