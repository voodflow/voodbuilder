<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Models\SitePage;

class SitePageController extends Controller
{
    public function show(string $slug): View
    {
        $page = SitePage::query()
            ->published()
            ->where('slug', $slug)
            ->where('is_home', false)
            ->firstOrFail();

        seo()->for($page);

        $data = [
            'page' => $page,
            'vpressSubTheme' => $page->resolvedSubTheme(),
        ];

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
