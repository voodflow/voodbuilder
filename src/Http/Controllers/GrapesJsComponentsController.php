<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\BuilderComponent;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentCategoryNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentImporter;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsImportCompatibilityAnalyzer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\TailblocksThemeTokenMigrator;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

class GrapesJsComponentsController extends Controller
{
    public function __construct(
        private readonly GrapesJsComponentImporter $importer,
    ) {}

    public function index(): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        if (! Schema::hasTable('voodbuilder_components')) {
            return response()->json(['components' => []]);
        }

        $backfillCss = [];

        $components = BuilderComponent::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function (BuilderComponent $component) use (&$backfillCss): array {
                return $this->toCatalogArray($component, $backfillCss);
            });

        if ($backfillCss !== []) {
            foreach ($backfillCss as $componentId => $payload) {
                BuilderComponent::query()
                    ->whereKey($componentId)
                    ->update($payload);
            }
        }

        return response()->json(['components' => $components]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'html' => ['required', 'string', 'max:200000'],
            'css' => ['nullable', 'string', 'max:250000'],
            'properties' => ['nullable', 'array'],
            'properties.*.id' => ['required_with:properties', 'string', 'max:64'],
            'properties.*.label' => ['required_with:properties', 'string', 'max:120'],
            'properties.*.type' => ['required_with:properties', 'string', 'in:text,url,image,rich_text'],
            'properties.*.default' => ['nullable', 'string', 'max:2000'],
        ]);

        $normalized = GrapesJsPastedComponentNormalizer::normalize($validated['html']);
        $css = GrapesJsPastedComponentNormalizer::mergeCss(
            filled($validated['css'] ?? null) ? trim((string) $validated['css']) : null,
            $normalized['css'],
        );

        $component = BuilderComponent::query()->create([
            'name' => $validated['name'],
            'category' => GrapesJsComponentCategoryNormalizer::normalize($validated['category'] ?? null),
            'description' => $validated['description'] ?? null,
            'html' => $normalized['html'],
            'css' => $css !== '' ? $css : null,
            'html_checksum' => GrapesJsPastedComponentNormalizer::htmlChecksum((string) $normalized['html']),
            'properties' => $validated['properties'] ?? [],
        ]);

        return response()->json(['component' => $this->toArray($component)], 201);
    }

    public function update(Request $request, BuilderComponent $component): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'html' => ['sometimes', 'string', 'max:200000'],
            'css' => ['nullable', 'string', 'max:250000'],
            'properties' => ['nullable', 'array'],
        ]);

        if (isset($validated['html'])) {
            $normalized = GrapesJsPastedComponentNormalizer::normalize($validated['html']);
            $validated['html'] = $normalized['html'];

            if (! array_key_exists('css', $validated)) {
                $validated['css'] = $normalized['css'];
            } elseif (filled($validated['css'] ?? null)) {
                $validated['css'] = GrapesJsPastedComponentNormalizer::mergeCss(
                    trim((string) $validated['css']),
                    $normalized['css'],
                );
            }
        }

        if (array_key_exists('category', $validated)) {
            $validated['category'] = GrapesJsComponentCategoryNormalizer::normalize($validated['category']);
        }

        if (array_key_exists('html', $validated)) {
            $validated['html_checksum'] = GrapesJsPastedComponentNormalizer::htmlChecksum((string) $validated['html']);
        }

        $component->update($validated);

        return response()->json(['component' => $this->toArray($component->fresh())]);
    }

    public function destroy(string $component): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        if (Schema::hasTable('voodbuilder_components')) {
            BuilderComponent::query()->whereKey($component)->delete();
        }

        return response()->json(['deleted' => true]);
    }

    public function compileCss(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'html' => ['required', 'string', 'max:200000'],
        ]);

        $normalized = GrapesJsPastedComponentNormalizer::normalize($validated['html']);
        $tailwindCss = GrapesJsPastedComponentNormalizer::compileTailwindCss($normalized['html']);
        $compatibility = GrapesJsImportCompatibilityAnalyzer::analyze(
            $validated['html'],
            $normalized['html'],
            $tailwindCss,
        );

        return response()->json([
            'html' => $normalized['html'],
            'css' => $tailwindCss,
            'compiled' => true,
            'compatibility' => $compatibility,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $validated = $request->validate([
            'components' => ['required', 'array', 'min:1', 'max:100'],
        ]);

        $created = $this->importer->import($validated['components']);

        return response()->json([
            'imported' => count($created),
            'components' => array_map(
                fn (BuilderComponent $component): array => $this->toArray($component),
                $created,
            ),
        ], 201);
    }

    /**
     * @param  array<string|int, array<string, string>>  $backfillCss
     * @return array<string, mixed>
     */
    protected function toCatalogArray(BuilderComponent $component, array &$backfillCss = []): array
    {
        $html = TailblocksThemeTokenMigrator::migrateHtml((string) $component->html);
        $resolved = GrapesJsPastedComponentNormalizer::resolveCatalogCss($html, $component->css, $component->html_checksum);
        $css = $resolved['css'];

        if (filled($resolved['cssToPersist'] ?? null)) {
            $backfillCss[(string) $component->id] = [
                'css' => (string) $resolved['cssToPersist'],
                'html_checksum' => (string) ($resolved['htmlChecksumToPersist'] ?? GrapesJsPastedComponentNormalizer::htmlChecksum($html)),
            ];
        }

        return [
            'id' => $component->id,
            'name' => $component->name,
            'category' => GrapesJsComponentCategoryNormalizer::normalize($component->category),
            'description' => $component->description,
            'html' => $html,
            'css' => $css !== '' ? $css : null,
            'properties' => $component->propertySchema(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toArray(BuilderComponent $component): array
    {
        $html = TailblocksThemeTokenMigrator::migrateHtml((string) $component->html);
        $css = GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml(
            $html,
            $component->css,
            $component->html_checksum,
        );

        return [
            'id' => $component->id,
            'name' => $component->name,
            'category' => GrapesJsComponentCategoryNormalizer::normalize($component->category),
            'description' => $component->description,
            'html' => $html,
            'css' => $css !== '' ? $css : null,
            'properties' => $component->propertySchema(),
        ];
    }
}
