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
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPageTemplateRemoteImporter;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsSmartButtonAnnotator;
use Voodflow\Voodbuilder\Support\GrapesJs\TemplateAuthoringBridge;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Licensing\EntitlementGate;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;

class GrapesJsPageTemplatesController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        abort_unless(TemplatesModule::isEnabled(), 403);

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
        TemplateAuthoringBridge::authorizeAuthoring();

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
        abort_unless(TemplatesModule::isEnabled(), 403);

        $pageTemplate->delete();

        return response()->json(['deleted' => true]);
    }

    public function import(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        TemplateAuthoringBridge::authorizeImportJson();

        $validated = $request->validate([
            'import' => ['required', 'array'],
        ]);

        $entries = GrapesJsPageTemplateBundle::extractTemplates($validated['import']);

        return response()->json([
            'templates' => $this->persistEntries($entries),
        ], 201);
    }

    public function importFromUrl(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        // Marketplace install link — Core consume path (no authoring plugin required).
        abort_unless(TemplatesModule::isEnabled(), 403);

        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
        ]);

        $bundle = GrapesJsPageTemplateRemoteImporter::fetchBundle($validated['url']);
        $entries = GrapesJsPageTemplateBundle::extractTemplates($bundle);

        if ($entries === []) {
            throw ValidationException::withMessages([
                'url' => __('voodbuilder::pro.page_templates.import_empty'),
            ]);
        }

        return response()->json([
            'templates' => $this->persistEntries($entries),
        ], 201);
    }

    public function catalog(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        EntitlementGate::authorize('templates.remote-install');

        $catalogUrl = trim((string) config('voodbuilder.page_templates.catalog_url', ''));

        if ($catalogUrl === '') {
            return response()->json(['templates' => []]);
        }

        return response()->json([
            'templates' => GrapesJsPageTemplateRemoteImporter::fetchCatalog($catalogUrl),
        ]);
    }

    public function installCatalogEntry(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        EntitlementGate::authorize('templates.remote-install');

        $validated = $request->validate([
            'bundle_url' => ['required', 'string', 'max:2000'],
        ]);

        $bundle = GrapesJsPageTemplateRemoteImporter::fetchBundle($validated['bundle_url']);
        $entries = GrapesJsPageTemplateBundle::extractTemplates($bundle);

        if ($entries === []) {
            throw ValidationException::withMessages([
                'bundle_url' => __('voodbuilder::pro.page_templates.import_empty'),
            ]);
        }

        return response()->json([
            'templates' => $this->persistEntries($entries),
        ], 201);
    }

    public function export(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);
        TemplateAuthoringBridge::authorizeExport();

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
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    protected function persistEntries(array $entries): array
    {
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

        return $created;
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
            'html' => GrapesJsSmartButtonAnnotator::annotate((string) ($template->html ?? '')),
            'css' => $template->css ?? '',
            'js' => $template->js ?? '',
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }
}
