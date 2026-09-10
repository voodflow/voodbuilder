<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\SiteSearch;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

/**
 * JSON suggestions for the header search palette.
 */
class SearchSuggestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $payload = SiteSearch::suggest($query);

        return response()->json([
            'query' => $query,
            'items' => $payload['items'],
            'total' => $payload['total'],
            'search_url' => VoodbuilderUrls::search(['q' => $query]),
        ]);
    }
}
