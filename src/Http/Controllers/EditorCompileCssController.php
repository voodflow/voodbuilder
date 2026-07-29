<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\Editor\EditorPastedComponentNormalizer;

/**
 * Canvas Tailwind JIT for the page/component editor.
 *
 * Lives in core VoodBuilder so live compile does not depend on the optional
 * Components companion plugin route (`…/components/compile-css`).
 */
final class EditorCompileCssController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $maxHtml = (int) config('voodbuilder.editor.payload.max_html_bytes', 500_000);

        $validated = $request->validate([
            'html' => ['required', 'string', 'max:'.$maxHtml],
            'scope' => ['nullable', 'string', 'in:page,component'],
        ]);

        $html = (string) $validated['html'];
        $scope = ($validated['scope'] ?? 'page') === 'component' ? 'component' : 'page';

        $css = $scope === 'page'
            ? EditorPastedComponentNormalizer::compilePageTailwindCss($html)
            : EditorPastedComponentNormalizer::compileTailwindCss($html);

        return response()->json([
            'css' => $css,
            'scope' => $scope,
        ]);
    }
}
