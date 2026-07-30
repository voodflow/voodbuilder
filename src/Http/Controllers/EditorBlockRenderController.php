<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockPreview;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * HTTP controller: Editor Block Render.
 */
class EditorBlockRenderController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'block' => ['required', 'string', 'max:120'],
            'config' => ['nullable', 'string', 'max:8000'],
        ]);

        $config = [];

        if (filled($validated['config'] ?? null)) {
            $decoded = json_decode((string) $validated['config'], true);
            $config = is_array($decoded) ? $decoded : [];
        }

        $html = EditorBlockPreview::render((string) $validated['block'], $config);

        if ($html === null) {
            abort(404);
        }

        return response()->json([
            'html' => $html,
        ]);
    }
}
