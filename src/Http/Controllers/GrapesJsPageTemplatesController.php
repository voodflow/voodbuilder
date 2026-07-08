<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Models\PageTemplate;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentCategoryNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPageTemplateBundle;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

class GrapesJsPageTemplatesController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        if (! Schema::hasTable('voodbuilder_page_templates')) {
            return response()->json(['templates' => []]);
        }

        $templates = PageTemplate::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (PageTemplate $template): array => $this->toArray($template));

        return response()->json(['templates' => $templates]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'html' => ['required', 'string', 'max:500000'],
            'css' => ['nullable', 'string', 'max:250000'],
            'js' => ['nullable', 'string', 'max:100000'],
        ]);

        $normalized = GrapesJsEditorGate::normalizePayload([
            'html' => $validated['html'],
            'css' => $validated['css'] ?? '',
            'js' => $validated['js'] ?? '',
            'project' => null,
        ], recompilePageCss: true);

        $template = PageTemplate::query()->create([
            'name' => $validated['name'],
            'category' => GrapesJsComponentCategoryNormalizer::normalize($validated['category'] ?? null),
            'description' => $validated['description'] ?? null,
            'html' => $normalized['html'],
            'css' => $normalized['css'] !== '' ? $normalized['css'] : null,
            'js' => filled($normalized['js'] ?? null) ? $normalized['js'] : null,
        ]);

        return response()->json(['template' => $this->toArray($template)], 201);
    }

    public function destroy(PageTemplate $pageTemplate): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $pageTemplate->delete();

        return response()->json(['deleted' => true]);
    }

    public function import(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'import' => ['required', 'array'],
        ]);

        $entries = GrapesJsPageTemplateBundle::extractTemplates($validated['import']);

        if ($entries === []) {
            throw ValidationException::withMessages([
                'import' => __('voodbuilder::pro.page_templates.import_empty'),
            ]);
        }

        $created = [];

        foreach ($entries as $entry) {
            $name = trim((string) ($entry['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $normalized = GrapesJsEditorGate::normalizePayload([
                'html' => (string) ($entry['html'] ?? ''),
                'css' => (string) ($entry['css'] ?? ''),
                'js' => (string) ($entry['js'] ?? ''),
                'project' => null,
            ], recompilePageCss: true);

            $template = PageTemplate::query()->create([
                'name' => $name,
                'category' => GrapesJsComponentCategoryNormalizer::normalize($entry['category'] ?? null),
                'description' => filled($entry['description'] ?? null) ? (string) $entry['description'] : null,
                'html' => $normalized['html'],
                'css' => $normalized['css'] !== '' ? $normalized['css'] : null,
                'js' => filled($normalized['js'] ?? null) ? $normalized['js'] : null,
            ]);

            $created[] = $this->toArray($template);
        }

        return response()->json(['templates' => $created], 201);
    }

    public function export(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['uuid'],
        ]);

        $query = PageTemplate::query()->orderBy('name');

        if (filled($validated['ids'] ?? null)) {
            $query->whereIn('id', $validated['ids']);
        }

        $payload = GrapesJsPageTemplateBundle::buildExportPayload($query->get()->all());

        return response()->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    protected function toArray(PageTemplate $template): array
    {
        return [
            'id' => $template->getKey(),
            'name' => $template->name,
            'category' => $template->category,
            'description' => $template->description,
            'html' => $template->html,
            'css' => $template->css ?? '',
            'js' => $template->js ?? '',
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }
}
