<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
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
            'project' => ['nullable', 'array'],
        ]);

        $normalized = GrapesJsEditorGate::normalizePayload([
            'html' => $validated['html'] ?? '',
            'css' => $validated['css'] ?? '',
            'project' => $validated['project'] ?? null,
        ]);

        $project = $normalized['project'];

        if (is_array($project)) {
            $encoded = json_encode($project);

            if ($encoded === false || strlen($encoded) > (int) config('vpress.grapesjs.payload.max_project_bytes', 2_000_000)) {
                throw ValidationException::withMessages([
                    'project' => __('The project payload is too large.'),
                ]);
            }
        }

        $sitePage->update([
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => $normalized['html'],
                'css' => $normalized['css'],
                'project' => $project,
            ],
        ]);

        return response()->json([
            'saved' => true,
            'updated_at' => $sitePage->fresh()?->updated_at?->toIso8601String(),
        ]);
    }
}
