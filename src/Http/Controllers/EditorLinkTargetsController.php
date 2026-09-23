<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\Editor\EditorLinkTargets;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * HTTP controller: Editor Link Targets.
 */
class EditorLinkTargetsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $routeName = trim((string) $request->query('route', ''));

        if ($routeName !== '') {
            $parameters = $request->query();
            unset($parameters['route']);

            return response()->json([
                'url' => EditorLinkTargets::resolveRoute($routeName, $parameters),
            ]);
        }

        return response()->json(EditorLinkTargets::catalog());
    }
}
