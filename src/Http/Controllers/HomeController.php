<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $page = SitePage::homePage();

        if ($page && filled($page->content)) {
            seo()->for($page);

            return view('vpress::pages.site-page', [
                'page' => $page,
                'vpressSubTheme' => $page->resolvedSubTheme(),
            ]);
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
}
