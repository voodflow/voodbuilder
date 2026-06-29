<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Vpress\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Vpress\Support\PageBuilderAccess;

class GrapesJsBindingsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $registry = app(BindingRegistry::class);

        return response()->json([
            'groups' => $registry->catalogGroupedByPackage(),
            'sources' => $registry->catalog(),
            'repeatSources' => app(ModelIntegrationRegistry::class)->repeatCatalog(),
        ]);
    }
}
