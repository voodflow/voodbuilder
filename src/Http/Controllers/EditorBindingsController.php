<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * HTTP controller: Editor Bindings.
 */
class EditorBindingsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $registry = app(BindingRegistry::class);

        return response()->json([
            'groups' => $registry->catalogGroupedByPackage(),
            'sources' => $registry->catalog(),
            // Empty when collections plugin is off / Community edition.
            'repeatSources' => DynamicDataCollectionsBridge::repeatSourcesCatalog(),
        ]);
    }
}
