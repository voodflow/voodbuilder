<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\EmptySiteGuidance;
use Voodflow\Voodbuilder\Support\SiteLocales;
use Voodflow\Voodbuilder\Support\SitePageAccess;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SitePageViewData;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;
use Voodflow\Vtuts\Support\Locales;

/**
 * HTTP controller: Home.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $locale = $this->resolvedHomeLocale();
        $page = SitePage::homePage($locale);

        if ($page === null && $locale !== null && SiteLocales::isValid($locale) && $locale !== SiteLocales::default()) {
            $defaultHome = SitePage::homePage(SiteLocales::default());

            if ($defaultHome !== null) {
                return redirect(VoodbuilderUrls::home(SiteLocales::default()));
            }
        }

        if ($page && ($page->usesEditorBuilder() || filled($page->content))) {
            seo()->for($page);

            app()->setLocale($page->locale);

            $page->loadMissing('credentials');

            $gate = SitePageAccess::denyReason($page, $request);

            if ($gate !== null) {
                session()->put('url.intended', $request->url());
            }

            return view('voodbuilder::pages.site-page', SitePageViewData::make($page, [
                'pageGate' => $gate,
                'pageGateOfferPassword' => SitePageAccess::offerPasswordBypass($page, $gate),
                'pageGateRequiresEmail' => SitePageAccess::offerPasswordBypass($page, $gate)
                    ? SitePageAccess::requiresEmailField($page)
                    : false,
                'pageGateLoginUrl' => SitePageAccess::loginUrl(),
                'pageGateRegisterUrl' => SitePageAccess::registerUrl(),
                'pageGateSubscribeUrl' => SitePageAccess::subscribeUrl(),
            ]));
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

        return view(config('voodbuilder.home.fallback_view', 'voodbuilder::pages.welcome'), [
            'voodbuilderSubTheme' => SubThemeResolver::siteDefault(),
            // Classic Blade nav/footer is not a Layout record — hide it on the empty state.
            'hideSiteNav' => true,
            'hideSiteFooter' => true,
            'welcomeNeedsLayout' => EmptySiteGuidance::needsChromeLayout(),
        ]);
    }

    protected function resolvedHomeLocale(): ?string
    {
        if (! class_exists(Locales::class)) {
            return null;
        }

        $queryLocale = request()->query('locale');

        if (is_string($queryLocale) && Locales::isValid($queryLocale)) {
            return $queryLocale;
        }

        $routeLocale = request()->route('locale');

        if (is_string($routeLocale) && Locales::isValid($routeLocale)) {
            return $routeLocale;
        }

        return SitePageResolver::preferredLocale();
    }
}
