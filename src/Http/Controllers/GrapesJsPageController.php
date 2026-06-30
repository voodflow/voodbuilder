<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingStorageNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate;

class GrapesJsPageController extends Controller
{
    public function update(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless(GrapesJsEditorGate::canEdit($sitePage), 403);
        abort_unless($sitePage->usesGrapesJsBuilder(), 422, 'Page does not use the GrapesJS builder.');

        $maxHtml = (int) config('voodbuilder.grapesjs.payload.max_html_bytes', 500_000);
        $maxCss = (int) config('voodbuilder.grapesjs.payload.max_css_bytes', 100_000);
        $maxJs = (int) config('voodbuilder.grapesjs.payload.max_js_bytes', 100_000);

        $validated = $request->validate([
            'html' => ['nullable', 'string', 'max:'.$maxHtml],
            'css' => ['nullable', 'string', 'max:'.$maxCss],
            'js' => ['nullable', 'string', 'max:'.$maxJs],
        ]);

        $normalized = GrapesJsEditorGate::normalizePayload([
            'html' => $validated['html'] ?? '',
            'css' => $validated['css'] ?? '',
            'js' => $validated['js'] ?? '',
            'project' => null,
        ]);

        $normalized['html'] = app(GrapesJsBindingStorageNormalizer::class)->normalizeHtml($normalized['html']);

        $sitePage->update([
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => $normalized['html'],
                'css' => $normalized['css'],
                'js' => $normalized['js'],
                // HTML/CSS are the source of truth for public render. Persisting
                // GrapesJS project JSON caused desync (removed blocks reappearing).
                'project' => null,
            ],
        ]);

        return response()->json([
            'saved' => true,
            'updated_at' => $sitePage->fresh()?->updated_at?->toIso8601String(),
        ]);
    }
}
