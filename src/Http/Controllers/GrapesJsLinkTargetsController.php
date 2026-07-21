<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsLinkTargets;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

class GrapesJsLinkTargetsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        return response()->json(GrapesJsLinkTargets::catalog());
    }
}
