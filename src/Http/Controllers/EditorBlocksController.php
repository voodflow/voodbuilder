<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorCommunityBlockCatalog;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorServerBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderSectionEditorBlocks;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * HTTP controller: Editor Blocks.
 */
class EditorBlocksController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $registry = app(EditorBlockRegistry::class);
        app(EditorDynamicBlockRegistry::class)->registerEditorBlocks($registry);

        if (config('voodbuilder.editor.site_blocks.header_footer', true)) {
            app(EditorServerBlockRegistry::class)->registerEditorBlocks($registry);
        }

        if (config('voodbuilder.editor.sections.enabled', true)) {
            VoodbuilderSectionEditorBlocks::register($registry);
        }

        $chromeLayoutEditor = EditorCommunityBlockCatalog::requestIsChromeLayoutEditor();

        return response()->json([
            'blocks' => $registry->toEditorBlocks($chromeLayoutEditor),
        ]);
    }
}
