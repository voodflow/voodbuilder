<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutManagedContent;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingStorageNormalizer;
use Voodflow\Voodbuilder\Support\Editor\ComponentRuntimeBridge;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\Editor\SitePageRevisionRecorder;
use Voodflow\Voodbuilder\Support\ThemePalette;

class EditorPageController extends Controller
{
    public function update(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless(EditorGate::canEdit($sitePage), 403);
        abort_unless($sitePage->usesEditorBuilder(), 422, 'Page does not use the Editor builder.');

        $maxHtml = (int) config('voodbuilder.editor.payload.max_html_bytes', 500_000);
        $maxCss = (int) config('voodbuilder.editor.payload.max_css_bytes', 100_000);
        $maxJs = (int) config('voodbuilder.editor.payload.max_js_bytes', 100_000);

        $validated = $request->validate([
            'html' => ['nullable', 'string', 'max:'.$maxHtml],
            'css' => ['nullable', 'string', 'max:'.$maxCss],
            'js' => ['nullable', 'string', 'max:'.$maxJs],
        ]);

        $normalized = EditorGate::normalizePayload([
            'html' => $validated['html'] ?? '',
            'css' => $validated['css'] ?? '',
            'js' => $validated['js'] ?? '',
            'project' => null,
        ], recompilePageCss: true);

        $normalized['html'] = app(EditorBindingStorageNormalizer::class)->normalizeHtml($normalized['html']);

        if (ChromeLayoutManagedContent::sitePageUsesChromeShell($sitePage)) {
            $normalized['html'] = ChromeLayoutManagedContent::stripSiteChromeFromPageHtml($normalized['html']);
        } else {
            $normalized['html'] = ChromeLayoutManagedContent::stripChromeEditorBleedFromPageHtml($normalized['html']);
        }

        $previousPayload = $sitePage->builder_payload ?? [];

        // Empty HTML is intentional (author cleared the page content slot). The editor
        // already guards against false-empty extracts when the slot still has children.

        $normalized['css'] = ThemePalette::stripEmbeddedPaletteOverrides($normalized['css']);

        $sitePage->update([
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => $normalized['html'],
                'css' => $normalized['css'],
                'js' => $normalized['js'],
                'project' => null,
            ],
        ]);

        ComponentRuntimeBridge::syncComponentCssLibraryFromPageHtml($normalized['html']);

        app(SitePageRevisionRecorder::class)
            ->recordIfChanged($sitePage, $previousPayload);

        return response()->json([
            'saved' => true,
            'css' => $normalized['css'],
            'updated_at' => $sitePage->fresh()?->updated_at?->toIso8601String(),
        ]);
    }
}
