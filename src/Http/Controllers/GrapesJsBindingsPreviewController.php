<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsEditorGate;

class GrapesJsBindingsPreviewController extends Controller
{
    public function __invoke(SitePage $sitePage): JsonResponse
    {
        abort_unless(GrapesJsEditorGate::canEdit($sitePage), 403);

        $registry = app(BindingRegistry::class);
        $context = BindingContext::forPage($sitePage);
        $values = [];

        foreach ($registry->catalog() as $source) {
            foreach ($source['fields'] as $field) {
                $key = $source['id'].'.'.$field['id'];
                $value = $registry->resolve($key, $context);

                if ($value !== null && $value !== '') {
                    $values[$key] = $value;
                }
            }

            $bindingSource = $registry->source($source['id']);

            if ($bindingSource !== null && method_exists($bindingSource, 'legacyFieldIds')) {
                foreach ($bindingSource->legacyFieldIds() as $legacyFieldId) {
                    $key = $source['id'].'.'.$legacyFieldId;
                    $value = $registry->resolve($key, $context);

                    if ($value !== null && $value !== '') {
                        $values[$key] = $value;
                    }
                }
            }
        }

        return response()->json([
            'values' => $values,
        ]);
    }
}
