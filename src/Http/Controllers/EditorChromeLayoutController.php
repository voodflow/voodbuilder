<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutReadingTypography;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Support\Editor\EditorChromeLayoutEditorGate;

/**
 * HTTP controller: Editor Chrome Layout.
 */
class EditorChromeLayoutController extends Controller
{
    public function update(Request $request, ChromeLayout $chromeLayout): JsonResponse
    {
        abort_unless(EditorChromeLayoutEditorGate::canEdit($chromeLayout), 403);

        $validated = $request->validate([
            'html' => ['nullable', 'string', 'max:500000'],
            'css' => ['nullable', 'string', 'max:250000'],
            'js' => ['nullable', 'string', 'max:100000'],
            'readingTypography' => ['nullable', 'array'],
            'readingTypography.font' => ['nullable', 'string', 'max:120'],
            'readingTypography.sidebarFont' => ['nullable', 'string', 'max:120'],
            'readingTypography.size' => ['nullable', 'string', 'max:16'],
            'readingTypography.typeScale' => ['nullable', 'array'],
            'readingTypography.sidebarTypeScale' => ['nullable', 'array'],
        ]);

        $normalized = EditorChromeLayoutEditorGate::normalizePayload([
            'html' => $validated['html'] ?? '',
            'css' => $validated['css'] ?? '',
            'js' => $validated['js'] ?? '',
        ]);

        $attributes = [
            'html' => $normalized['html'],
            'css' => $normalized['css'] !== '' ? $normalized['css'] : null,
            'js' => filled($normalized['js']) ? $normalized['js'] : null,
        ];

        if (array_key_exists('readingTypography', $validated) && is_array($validated['readingTypography'])) {
            $attributes = [
                ...$attributes,
                ...ChromeLayoutReadingTypography::normalizeSavePayload($validated['readingTypography']),
            ];
        }

        $chromeLayout->update($attributes);

        ChromeLayoutResolver::forgetCache();

        $fresh = $chromeLayout->fresh();
        $reading = ChromeLayoutReadingTypography::resolve($fresh);

        return response()->json([
            'saved' => true,
            'css' => $normalized['css'],
            'readingTypography' => [
                'font' => $reading['font'],
                'sidebarFont' => $reading['sidebarFont'],
                'size' => $reading['size'],
                'typeScale' => $reading['typeScale'],
                'sidebarTypeScale' => $reading['sidebarTypeScale'],
                'cssVariables' => $reading['cssVariables'],
                'stylesheetUrls' => $reading['stylesheetUrls'],
            ],
            'updated_at' => $fresh?->updated_at?->toIso8601String(),
        ]);
    }
}
