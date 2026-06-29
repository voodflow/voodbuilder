<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Voodbuilder\Support\SiteSearch;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

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
            ? __('voodbuilder::search.seo_title', ['query' => $query])
            : __('voodbuilder::search.title');

        seo()->for(new SEOData(
            title: $seoTitle,
            description: __('voodbuilder::search.description'),
            robots: $query !== '' ? 'noindex, follow' : 'index, follow',
        ));

        return view('voodbuilder::pages.search', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'total' => $total,
            'availableTypes' => SiteSearch::availableTypes(),
            'typeLabels' => SiteSearch::typeLabels(),
            'searchUrl' => VoodbuilderUrls::search(),
        ]);
    }
}
