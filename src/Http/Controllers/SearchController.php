<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Voodflow\Voodbuilder\Support\SearchSettings;
use Voodflow\Voodbuilder\Support\SiteSearch;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

/**
 * HTTP controller: Search.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $type = filled($request->query('type')) ? (string) $request->query('type') : null;
        $page = max(1, (int) $request->query('page', 1));

        if ($type !== null && ! in_array($type, SiteSearch::availableTypes(), true)) {
            $type = null;
        }

        $grouped = $query !== '' ? SiteSearch::search($query) : [];
        $typeCounts = collect($grouped)
            ->map(fn ($items): int => $items->count())
            ->all();
        $total = SiteSearch::totalCount($grouped);

        $scoped = $type !== null
            ? (isset($grouped[$type]) ? [$type => $grouped[$type]] : [])
            : $grouped;

        $flat = SiteSearch::flatten($scoped);
        $paginator = $query !== '' && $total > 0
            ? SiteSearch::paginate($flat, $page, $query, $type)
            : null;

        $filterTypes = collect(SiteSearch::availableTypes())
            ->filter(fn (string $id): bool => ($typeCounts[$id] ?? 0) > 0)
            ->values()
            ->all();

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
            'paginator' => $paginator,
            'total' => $total,
            'typeCounts' => $typeCounts,
            'availableTypes' => $filterTypes,
            'typeLabels' => SiteSearch::typeLabels(),
            'searchUrl' => VoodbuilderUrls::search(),
            'perPage' => SearchSettings::perPage(),
        ]);
    }
}
