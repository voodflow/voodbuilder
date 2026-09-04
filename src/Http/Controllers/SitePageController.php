<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SitePageAccess;
use Voodflow\Voodbuilder\Support\SitePageMenuPath;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SitePageViewData;

/**
 * HTTP controller: Site Page.
 */
class SitePageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        // Always read nested segment from the route bag — do not rely on method-arg
        // order ({section}/{slug} vs $slug/$section), which can swap under dispatch.
        $section = $request->route('section');
        $section = is_string($section) && $section !== '' ? $section : null;
        $slug = is_string($request->route('slug')) && $request->route('slug') !== ''
            ? (string) $request->route('slug')
            : $slug;

        $resolvedSlug = SitePageMenuPath::resolveSlugFromRoute($section, $slug);
        $page = SitePageResolver::publishedFromSlug($resolvedSlug);

        if ($page->is_home) {
            abort(404);
        }

        if (! PageBuilderAccess::userCanUsePageBuilder() && ! $page->published) {
            abort(404);
        }

        app()->setLocale($page->locale);

        seo()->for($page);

        $page->loadMissing('credentials');

        $gate = SitePageAccess::denyReason($page, $request);

        if ($gate !== null) {
            session()->put('url.intended', $request->url());
        }

        $data = SitePageViewData::make($page, [
            'pageGate' => $gate,
            'pageGateOfferPassword' => SitePageAccess::offerPasswordBypass($page, $gate),
            'pageGateRequiresEmail' => SitePageAccess::offerPasswordBypass($page, $gate)
                ? SitePageAccess::requiresEmailField($page)
                : false,
            'pageGateLoginUrl' => SitePageAccess::loginUrl(),
            'pageGateRegisterUrl' => SitePageAccess::registerUrl(),
            'pageGateSubscribeUrl' => SitePageAccess::subscribeUrl(),
        ]);

        if (filled($page->section)) {
            $data['sectionHome'] = SitePage::sectionHomePage($page->section);
            $data['sectionPosts'] = SitePage::sectionArticles($page->section);
        }

        if ($page->isSectionHome() && $gate === null) {
            return view('voodbuilder::pages.section-index', $data);
        }

        return view('voodbuilder::pages.site-page', $data);
    }
}
