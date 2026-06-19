<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Vpress\Support\PageBuilderAccess;

class GrapesJsBlocksController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        return response()->json([
            'blocks' => app(GrapesJsBlockRegistry::class)->toEditorBlocks(),
        ]);
    }
}
