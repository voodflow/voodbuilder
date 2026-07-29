<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Voodflow\Voodbuilder\Support\MarkdownCodeBlocks;

final class EditorCodeHighlightController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string', 'max:50000'],
        ]);

        return response()->json([
            'html' => MarkdownCodeBlocks::toHighlightedHtml(
                (string) $validated['language'],
                (string) $validated['code'],
            ),
        ]);
    }
}
