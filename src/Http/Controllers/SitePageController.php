<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\PageBuilderAccess;
use Voodflow\Vpress\Support\SitePageResolver;
use Voodflow\Vpress\Support\SitePageViewData;

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
            return view('vpress::pages.section-index', $data);
        }

        return view('vpress::pages.site-page', $data);
    }
}
