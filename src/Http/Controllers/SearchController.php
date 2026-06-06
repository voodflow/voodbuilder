<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Vpress\Support\SiteSearch;
use Voodflow\Vpress\Support\VpressUrls;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $type = filled($request->query('type')) ? (string) $request->query('type') : null;

        if ($type !== null && ! in_array($type, SiteSearch::availableTypes(), true)) {
            $type = null;
        }

        $results = SiteSearch::search($query, $type);
        $total = SiteSearch::totalCount($results);

        $seoTitle = $query !== ''
            ? __('vpress::search.seo_title', ['query' => $query])
            : __('vpress::search.title');

        seo()->for(new SEOData(
            title: $seoTitle,
            description: __('vpress::search.description'),
            robots: $query !== '' ? 'noindex, follow' : 'index, follow',
        ));

        return view('vpress::pages.search', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'total' => $total,
            'availableTypes' => SiteSearch::availableTypes(),
            'searchUrl' => VpressUrls::search(),
        ]);
    }
}
