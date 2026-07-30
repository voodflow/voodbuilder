<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SitePageViewData;

/**
 * HTTP controller: Site Page.
 */
class SitePageController extends Controller
{
    public function show(string $slug): View
    {
        $page = SitePageResolver::publishedFromSlug($slug);

        if ($page->is_home) {
            abort(404);
        }

        if (! PageBuilderAccess::userCanUsePageBuilder() && ! $page->published) {
            abort(404);
        }

        app()->setLocale($page->locale);

        seo()->for($page);

        $data = SitePageViewData::make($page);

        if (filled($page->section)) {
            $data['sectionHome'] = SitePage::sectionHomePage($page->section);
            $data['sectionPosts'] = SitePage::sectionArticles($page->section);
        }

        if ($page->isSectionHome()) {
            return view('voodbuilder::pages.section-index', $data);
        }

        return view('voodbuilder::pages.site-page', $data);
    }
}
