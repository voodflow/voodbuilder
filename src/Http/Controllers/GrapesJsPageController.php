<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsEditorGate;

class GrapesJsPageController extends Controller
{
    public function update(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless(GrapesJsEditorGate::canEdit($sitePage), 403);

        $validated = $request->validate([
            'html' => ['nullable', 'string'],
            'css' => ['nullable', 'string'],
            'project' => ['nullable', 'array'],
        ]);

        $sitePage->update([
            'builder_payload' => [
                'html' => $validated['html'] ?? '',
                'css' => $validated['css'] ?? '',
                'project' => $validated['project'] ?? null,
            ],
        ]);

        return response()->json([
            'saved' => true,
            'updated_at' => $sitePage->fresh()?->updated_at?->toIso8601String(),
        ]);
    }
}
