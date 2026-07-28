<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingMediaUrlResolver;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationListResolver;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate;

class GrapesJsBindingsPreviewController extends Controller
{
    public function __invoke(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless(GrapesJsEditorGate::canEdit($sitePage), 403);

        $registry = app(BindingRegistry::class);
        $context = BindingContext::forEditorPreview($sitePage);
        $values = [];

        foreach ($registry->catalog() as $source) {
            foreach ($source['fields'] as $field) {
                $key = $source['id'].'.'.$field['id'];
                $value = $registry->resolve($key, $context);

                if ($value !== null && $value !== '') {
                    $values[$key] = $this->normalizePreviewValue((string) $value, $field['type'] ?? 'text');
                }
            }

            $bindingSource = $registry->source($source['id']);

            if ($bindingSource !== null && method_exists($bindingSource, 'legacyFieldIds')) {
                foreach ($bindingSource->legacyFieldIds() as $legacyFieldId) {
                    $key = $source['id'].'.'.$legacyFieldId;
                    $value = $registry->resolve($key, $context);

                    if ($value !== null && $value !== '') {
                        $values[$key] = $this->normalizePreviewValue((string) $value, 'text');
                    }
                }
            }
        }

        return response()->json([
            'values' => $values,
            'listValues' => $this->listPreviewValues($request, $sitePage, $registry),
        ]);
    }

    /**
     * @return array<string, list<array<string, string>>>
     */
    protected function listPreviewValues(Request $request, SitePage $sitePage, BindingRegistry $registry): array
    {
        if (! DynamicDataCollectionsBridge::moduleEnabled()) {
            return [];
        }

        if (! class_exists(ModelIntegrationListResolver::class) || ! class_exists(RepeatListRegistry::class)) {
            return [];
        }

        $listValues = [];
        $lists = app(ModelIntegrationListResolver::class);
        $integrations = app(ModelIntegrationRegistry::class);
        $repeatConfigs = $this->repeatConfigsFromRequest($request, $integrations);

        if ($repeatConfigs === []) {
            $repeatConfigs = array_map(
                static fn (array $repeatSource): array => [
                    'key' => $repeatSource['id'],
                    'sort' => $repeatSource['defaultSort'] ?? 'id',
                    'dir' => $repeatSource['defaultDirection'] ?? 'desc',
                    'limit' => 12,
                    'offset' => 0,
                ],
                DynamicDataCollectionsBridge::repeatSourcesCatalog(),
            );
        }

        foreach ($repeatConfigs as $config) {
            $repeatKey = (string) ($config['key'] ?? '');
            $sort = (string) ($config['sort'] ?? 'id');
            $dir = (string) ($config['dir'] ?? 'desc');
            $limit = max(1, min(24, (int) ($config['limit'] ?? 12)));
            $offset = max(0, min(100, (int) ($config['offset'] ?? 0)));
            $itemSourceId = preg_replace('/\.list$/', '.item', $repeatKey) ?? '';

            if ($repeatKey === '' || $itemSourceId === '') {
                continue;
            }

            $itemSource = $registry->source($itemSourceId);

            if ($itemSource === null) {
                continue;
            }

            $rows = [];
            $filters = is_array($config['filters'] ?? null) ? $config['filters'] : [];
            $listKey = self::listValuesKey($repeatKey, $sort, $dir, $offset, $filters);

            if (isset($listValues[$listKey])) {
                continue;
            }

            foreach ($lists->resolve($repeatKey, $limit, $sort, $dir, $offset, $filters) as $record) {
                $row = [];
                $context = BindingContext::forEditorPreview($sitePage)->withRepeatItem($record);

                foreach ($itemSource->fields() as $field) {
                    $value = $registry->resolve($itemSourceId.'.'.$field->id, $context);

                    if ($value !== null && $value !== '') {
                        $row[$field->id] = $this->normalizePreviewValue((string) $value, $field->type);
                    }
                }

                if ($row !== []) {
                    $rows[] = $row;
                }
            }

            if ($rows !== []) {
                $listValues[$listKey] = $rows;
            }
        }

        return $listValues;
    }

    /**
     * @return list<array{key: string, sort?: string, dir?: string, limit?: int, offset?: int}>
     */
    protected function repeatConfigsFromRequest(Request $request, ModelIntegrationRegistry $integrations): array
    {
        if (! DynamicDataCollectionsBridge::moduleEnabled() || ! class_exists(RepeatListRegistry::class)) {
            return [];
        }

        $raw = $request->query('repeats');

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $packageLists = app(RepeatListRegistry::class);
        $configs = [];

        foreach ($decoded as $config) {
            if (! is_array($config)) {
                continue;
            }

            $key = trim((string) ($config['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            if ($integrations->findByListKey($key) === null && ! $packageLists->has($key)) {
                continue;
            }

            $configs[] = [
                'key' => $key,
                'sort' => (string) ($config['sort'] ?? 'id'),
                'dir' => (string) ($config['dir'] ?? 'desc'),
                'limit' => (int) ($config['limit'] ?? 12),
                'offset' => (int) ($config['offset'] ?? 0),
                'filters' => self::normalizeFilters($config['filters'] ?? []),
            ];
        }

        return $configs;
    }

    /**
     * @param  mixed  $filters
     * @return array<string, string>
     */
    public static function normalizeFilters(mixed $filters): array
    {
        if (! is_array($filters)) {
            return [];
        }

        $normalized = [];

        foreach ($filters as $key => $value) {
            $key = trim((string) $key);
            $value = trim((string) $value);

            if ($key === '' || $value === '') {
                continue;
            }

            $normalized[$key] = $value;
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  array<string, string>  $filters
     */
    public static function listValuesKey(
        string $repeatKey,
        string $sort,
        string $dir,
        int $offset = 0,
        array $filters = [],
    ): string {
        $base = $repeatKey.'|'.$sort.'|'.$dir.'|'.$offset;
        $normalized = self::normalizeFilters($filters);

        if ($normalized === []) {
            return $base;
        }

        $parts = [];

        foreach ($normalized as $key => $value) {
            $parts[] = $key.':'.$value;
        }

        return $base.'|'.implode(',', $parts);
    }

    protected function normalizePreviewValue(string $value, string $fieldType): string
    {
        if ($fieldType !== 'image') {
            return $value;
        }

        return BindingMediaUrlResolver::normalizeForEditor($value);
    }
}
