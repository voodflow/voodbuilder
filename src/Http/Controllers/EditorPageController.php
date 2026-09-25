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
use Voodflow\Voodbuilder\Support\Editor\EditorPastedComponentNormalizer;
use Voodflow\Voodbuilder\Support\Editor\PageCssArtifactStore;
use Voodflow\Voodbuilder\Support\Editor\SitePageRevisionRecorder;
use Voodflow\Voodbuilder\Support\ThemePalette;

/**
 * HTTP controller: Editor Page.
 */
class EditorPageController extends Controller
{
    public const SAVE_INPUT_HASH_KEY = 'save_input_hash';

    public const CSS_CLASS_FINGERPRINT_KEY = 'css_class_fingerprint';

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
            // Canvas live JIT utilities — lets Save skip Node when the editor already
            // compiled new classes (e.g. bg-cyan-400 visible before Save).
            'live_css' => ['nullable', 'string', 'max:'.$maxCss],
        ]);

        $incomingHtml = (string) ($validated['html'] ?? '');
        $incomingCss = (string) ($validated['css'] ?? '');
        $incomingJs = (string) ($validated['js'] ?? '');
        $liveUtilitiesCss = (string) ($validated['live_css'] ?? '');
        $inputHash = hash('sha256', $incomingHtml."\0".$incomingCss."\0".$incomingJs);

        $previousPayload = $sitePage->builder_payload ?? [];
        $recorder = app(SitePageRevisionRecorder::class);

        // Identical Save payload: skip sanitize/JIT/DB write — only clear parked drafts.
        if (($previousPayload[self::SAVE_INPUT_HASH_KEY] ?? null) === $inputHash) {
            $recorder->discardAutosaves($sitePage);

            return response()->json([
                'saved' => true,
                'css_unchanged' => true,
                'updated_at' => $sitePage->updated_at?->toIso8601String(),
            ]);
        }

        $previousFullCss = PageCssArtifactStore::resolveCss($previousPayload);
        $previousFingerprint = isset($previousPayload[self::CSS_CLASS_FINGERPRINT_KEY])
            && is_string($previousPayload[self::CSS_CLASS_FINGERPRINT_KEY])
            ? $previousPayload[self::CSS_CLASS_FINGERPRINT_KEY]
            : null;

        $normalized = EditorGate::normalizePayload([
            'html' => $incomingHtml,
            'css' => $incomingCss,
            'js' => $incomingJs,
            'project' => null,
        ], recompilePageCss: true, previousFullCss: $previousFullCss !== '' ? $previousFullCss : null, previousClassFingerprint: $previousFingerprint, liveUtilitiesCss: $liveUtilitiesCss !== '' ? $liveUtilitiesCss : null);

        $normalized['html'] = app(EditorBindingStorageNormalizer::class)->normalizeHtml($normalized['html']);

        if (ChromeLayoutManagedContent::sitePageUsesChromeShell($sitePage)) {
            $normalized['html'] = ChromeLayoutManagedContent::stripSiteChromeFromPageHtml($normalized['html']);
        } else {
            $normalized['html'] = ChromeLayoutManagedContent::stripChromeEditorBleedFromPageHtml($normalized['html']);
        }

        // Empty HTML is intentional (author cleared the page content slot). The editor
        // already guards against false-empty extracts when the slot still has children.

        $fullCss = ThemePalette::stripEmbeddedPaletteOverrides($normalized['css']);
        $authorCss = EditorPastedComponentNormalizer::manualPageCssFromStoredCss($incomingCss);

        if ($authorCss === '') {
            $authorCss = EditorPastedComponentNormalizer::manualPageCssFromStoredCss($fullCss);
        }

        $cssUnchanged = $previousFullCss !== '' && hash_equals($previousFullCss, $fullCss);

        $cssStorage = PageCssArtifactStore::persistForPage($sitePage, $fullCss, $authorCss);

        $builderPayload = [
            'html' => $normalized['html'],
            'css' => $cssStorage['css'],
            'js' => $normalized['js'],
            'fonts' => $normalized['fonts'] ?? [],
            'project' => null,
            self::SAVE_INPUT_HASH_KEY => $inputHash,
            self::CSS_CLASS_FINGERPRINT_KEY => $normalized['css_class_fingerprint']
                ?? EditorPastedComponentNormalizer::pageUtilityClassFingerprint($normalized['html']),
        ];

        if ($cssStorage[PageCssArtifactStore::META_KEY] !== null) {
            $builderPayload[PageCssArtifactStore::META_KEY] = $cssStorage[PageCssArtifactStore::META_KEY];
        }

        $sitePage->update([
            'builder' => PageBuilder::Visual,
            'builder_payload' => $builderPayload,
        ]);

        ComponentRuntimeBridge::syncComponentCssLibraryFromPageHtml($normalized['html']);

        $recorder->recordIfChanged($sitePage, $previousPayload);
        // The work is published, so there is nothing left to recover. Keeping the autosaves
        // would greet the author with a recovery offer for the page they just saved.
        $recorder->discardAutosaves($sitePage);

        return response()->json([
            'saved' => true,
            // Full resolved sheet so the canvas live JIT can refresh without a second compile.
            // Omit when unchanged so the client skips a redundant stylesheet apply.
            'css' => $cssUnchanged ? null : $fullCss,
            'css_unchanged' => $cssUnchanged,
            'css_artifact' => $cssStorage[PageCssArtifactStore::META_KEY],
            'updated_at' => $sitePage->fresh()?->updated_at?->toIso8601String(),
        ]);
    }
}
