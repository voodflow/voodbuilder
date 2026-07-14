<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsChromeLayoutEditorGate;

class GrapesJsChromeLayoutController extends Controller
{
    public function update(Request $request, ChromeLayout $chromeLayout): JsonResponse
    {
        abort_unless(GrapesJsChromeLayoutEditorGate::canEdit($chromeLayout), 403);

        $validated = $request->validate([
            'html' => ['nullable', 'string', 'max:500000'],
            'css' => ['nullable', 'string', 'max:250000'],
            'js' => ['nullable', 'string', 'max:100000'],
        ]);

        $normalized = GrapesJsChromeLayoutEditorGate::normalizePayload([
            'html' => $validated['html'] ?? '',
            'css' => $validated['css'] ?? '',
            'js' => $validated['js'] ?? '',
        ]);

        $chromeLayout->update([
            'html' => $normalized['html'],
            'css' => $normalized['css'] !== '' ? $normalized['css'] : null,
            'js' => filled($normalized['js']) ? $normalized['js'] : null,
        ]);

        ChromeLayoutResolver::forgetCache();

        return response()->json([
            'saved' => true,
            'css' => $normalized['css'],
            'updated_at' => $chromeLayout->fresh()?->updated_at?->toIso8601String(),
        ]);
    }
}
