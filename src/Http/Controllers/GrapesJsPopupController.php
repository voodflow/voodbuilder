<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPopupEditorGate;

class GrapesJsPopupController extends Controller
{
    public function update(Request $request, BuilderPopup $popup): JsonResponse
    {
        abort_unless(GrapesJsPopupEditorGate::canEdit($popup), 403);

        $validated = $request->validate([
            'html' => ['nullable', 'string', 'max:500000'],
            'css' => ['nullable', 'string', 'max:250000'],
            'js' => ['nullable', 'string', 'max:100000'],
        ]);

        $normalized = GrapesJsPopupEditorGate::normalizePayload([
            'html' => $validated['html'] ?? '',
            'css' => $validated['css'] ?? '',
            'js' => $validated['js'] ?? '',
        ]);

        $popup->update([
            'html' => $normalized['html'],
            'css' => $normalized['css'] !== '' ? $normalized['css'] : null,
            'js' => filled($normalized['js']) ? $normalized['js'] : null,
        ]);

        return response()->json([
            'saved' => true,
            'css' => $normalized['css'],
            'updated_at' => $popup->fresh()?->updated_at?->toIso8601String(),
        ]);
    }
}
