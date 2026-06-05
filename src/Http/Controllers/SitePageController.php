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

        return view('vpress::pages.site-page', ['page' => $page]);
    }
}
