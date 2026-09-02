<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Enums\SitePageRevisionKind;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageRevision;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\Editor\SitePageRevisionRecorder;

/**
 * HTTP controller: Editor Page Revisions.
 */
class EditorPageRevisionsController extends Controller
{
    public function index(SitePage $sitePage): JsonResponse
    {
        abort_unless(EditorGate::canEdit($sitePage), 403);

        // Manual only: the history list is the author's own save points. Unattended
        // autosaves are surfaced separately, as a recovery offer.
        $revisions = SitePageRevision::query()
            ->where('site_page_id', $sitePage->getKey())
            ->manual()
            ->with('creator:id,name')
            ->orderByDesc('id')
            ->limit((int) config('voodbuilder.editor.revisions.max_to_keep', 50))
            ->get()
            ->map(fn (SitePageRevision $revision): array => [
                'id' => $revision->id,
                'created_at' => $revision->created_at?->toIso8601String(),
                'created_by' => $revision->creator?->name,
                'builder_payload' => $revision->builder_payload,
            ]);

        return response()->json([
            'revisions' => $revisions,
            'autosave' => $this->autosaveOffer($sitePage),
        ]);
    }

    /**
     * Park the current editor state without publishing it.
     */
    public function autosave(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless(EditorGate::canEdit($sitePage), 403);
        abort_unless($sitePage->usesEditorBuilder(), 422, 'Page does not use the Editor builder.');

        $validated = $request->validate([
            'html' => ['nullable', 'string', 'max:'.(int) config('voodbuilder.editor.payload.max_html_bytes', 500_000)],
            'css' => ['nullable', 'string', 'max:'.(int) config('voodbuilder.editor.payload.max_css_bytes', 100_000)],
            'js' => ['nullable', 'string', 'max:'.(int) config('voodbuilder.editor.payload.max_js_bytes', 100_000)],
        ]);

        $revision = app(SitePageRevisionRecorder::class)->recordAutosave(
            $sitePage,
            EditorGate::sanitizeDraftPayload($validated) + ['project' => null],
        );

        return response()->json([
            'autosaved' => $revision !== null,
            'revision_id' => $revision?->id,
            'created_at' => $revision?->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Drop the safety net once the author no longer needs it.
     */
    public function discardAutosaves(SitePage $sitePage): JsonResponse
    {
        abort_unless(EditorGate::canEdit($sitePage), 403);

        app(SitePageRevisionRecorder::class)->discardAutosaves($sitePage);

        return response()->json(['discarded' => true]);
    }

    public function restore(Request $request, SitePage $sitePage, SitePageRevision $revision): JsonResponse
    {
        abort_unless(EditorGate::canEdit($sitePage), 403);
        abort_unless((int) $revision->site_page_id === (int) $sitePage->getKey(), 404);

        $previousPayload = $sitePage->builder_payload ?? [];
        $isAutosave = SitePageRevisionKind::normalize($revision->kind) === SitePageRevisionKind::Autosave;
        $payload = $revision->builder_payload ?? [];

        // Manual revisions were already published once, so they are restored verbatim.
        // An autosave never was: it skipped the Tailwind and font passes to keep the
        // timer cheap, so publishing it raw would put the page live without its
        // stylesheet.
        if ($isAutosave) {
            $payload = EditorGate::normalizePayload($payload, recompilePageCss: true);
        }

        $sitePage->update([
            'builder_payload' => $payload,
        ]);

        app(SitePageRevisionRecorder::class)->recordIfChanged($sitePage, $previousPayload);

        // Recovering an autosave publishes it, so the net has served its purpose. Leaving
        // it would keep offering recovery for work that is now the live page.
        if ($isAutosave) {
            app(SitePageRevisionRecorder::class)->discardAutosaves($sitePage);
        }

        return response()->json([
            'restored' => true,
            'builder_payload' => $sitePage->fresh()?->builder_payload,
        ]);
    }

    /**
     * The recovery offer shown when the editor reopens.
     *
     * @return array{id: int, created_at: string|null, created_by: string|null}|null
     */
    private function autosaveOffer(SitePage $sitePage): ?array
    {
        $autosave = app(SitePageRevisionRecorder::class)->latestAutosave($sitePage);

        if ($autosave === null) {
            return null;
        }

        return [
            'id' => $autosave->id,
            'created_at' => $autosave->created_at?->toIso8601String(),
            'created_by' => $autosave->creator?->name,
            'builder_payload' => $autosave->builder_payload,
        ];
    }
}
