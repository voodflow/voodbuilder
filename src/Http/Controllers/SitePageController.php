<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\AdminAccess;
use Voodflow\Vpress\Support\SitePageViewData;

class SitePageController extends Controller
{
    public function show(string $slug): View
    {
        $page = SitePage::query()
            ->where('slug', $slug)
            ->where('is_home', false)
            ->when(
                ! AdminAccess::userCanAccessPanel(),
                fn ($query) => $query->published(),
            )
            ->firstOrFail();

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
