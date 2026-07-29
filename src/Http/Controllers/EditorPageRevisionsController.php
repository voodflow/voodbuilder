<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageRevision;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\Editor\SitePageRevisionRecorder;

class EditorPageRevisionsController extends Controller
{
    public function index(SitePage $sitePage): JsonResponse
    {
        abort_unless(EditorGate::canEdit($sitePage), 403);

        $revisions = SitePageRevision::query()
            ->where('site_page_id', $sitePage->getKey())
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

        return response()->json(['revisions' => $revisions]);
    }

    public function restore(Request $request, SitePage $sitePage, SitePageRevision $revision): JsonResponse
    {
        abort_unless(EditorGate::canEdit($sitePage), 403);
        abort_unless((int) $revision->site_page_id === (int) $sitePage->getKey(), 404);

        $previousPayload = $sitePage->builder_payload ?? [];

        $sitePage->update([
            'builder_payload' => $revision->builder_payload,
        ]);

        app(SitePageRevisionRecorder::class)->recordIfChanged($sitePage, $previousPayload);

        return response()->json([
            'restored' => true,
            'builder_payload' => $sitePage->fresh()?->builder_payload,
        ]);
    }
}
