<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Enums\PageBuilder;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsEditorGate;

class GrapesJsPageController extends Controller
{
    public function update(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless(GrapesJsEditorGate::canEdit($sitePage), 403);
        abort_unless($sitePage->usesGrapesJsBuilder(), 422, 'Page does not use the GrapesJS builder.');

        $maxHtml = (int) config('vpress.grapesjs.payload.max_html_bytes', 500_000);
        $maxCss = (int) config('vpress.grapesjs.payload.max_css_bytes', 100_000);

        $validated = $request->validate([
            'html' => ['nullable', 'string', 'max:'.$maxHtml],
            'css' => ['nullable', 'string', 'max:'.$maxCss],
        ]);

        $normalized = GrapesJsEditorGate::normalizePayload([
            'html' => $validated['html'] ?? '',
            'css' => $validated['css'] ?? '',
            'project' => null,
        ]);

        $sitePage->update([
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => $normalized['html'],
                'css' => $normalized['css'],
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
