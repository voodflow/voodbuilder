<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderSectionGrapesJsBlocks;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

class GrapesJsBlocksController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $registry = app(GrapesJsBlockRegistry::class);
        app(GrapesJsDynamicBlockRegistry::class)->registerEditorBlocks($registry);

        if (config('voodbuilder.grapesjs.site_blocks.header_footer', true)) {
            app(GrapesJsServerBlockRegistry::class)->registerEditorBlocks($registry);
        }

        if (config('voodbuilder.grapesjs.sections.enabled', true)) {
            VoodbuilderSectionGrapesJsBlocks::register($registry);
        }

        return response()->json([
            'blocks' => $registry->toEditorBlocks(),
        ]);
    }
}
