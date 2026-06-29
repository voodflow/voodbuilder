<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationListResolver;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate;

class GrapesJsBindingsPreviewController extends Controller
{
    public function __invoke(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless(GrapesJsEditorGate::canEdit($sitePage), 403);

        $registry = app(BindingRegistry::class);
        $context = BindingContext::forPage($sitePage);
        $values = [];

        foreach ($registry->catalog() as $source) {
            foreach ($source['fields'] as $field) {
                $key = $source['id'].'.'.$field['id'];
                $value = $registry->resolve($key, $context);

                if ($value !== null && $value !== '') {
                    $values[$key] = $value;
                }
            }

            $bindingSource = $registry->source($source['id']);

            if ($bindingSource !== null && method_exists($bindingSource, 'legacyFieldIds')) {
                foreach ($bindingSource->legacyFieldIds() as $legacyFieldId) {
                    $key = $source['id'].'.'.$legacyFieldId;
                    $value = $registry->resolve($key, $context);

                    if ($value !== null && $value !== '') {
                        $values[$key] = $value;
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
                ],
                $integrations->repeatCatalog(),
            );
        }

        foreach ($repeatConfigs as $config) {
            $repeatKey = (string) ($config['key'] ?? '');
            $sort = (string) ($config['sort'] ?? 'id');
            $dir = (string) ($config['dir'] ?? 'desc');
            $limit = max(1, min(24, (int) ($config['limit'] ?? 12)));
            $itemSourceId = preg_replace('/\.list$/', '.item', $repeatKey) ?? '';

            if ($repeatKey === '' || $itemSourceId === '') {
                continue;
            }

            $itemSource = $registry->source($itemSourceId);

            if ($itemSource === null) {
                continue;
            }

            $rows = [];
            $listKey = self::listValuesKey($repeatKey, $sort, $dir);

            if (isset($listValues[$listKey])) {
                continue;
            }

            foreach ($lists->resolve($repeatKey, $limit, $sort, $dir) as $record) {
                $row = [];
                $context = BindingContext::forPage($sitePage, $record);

                foreach ($itemSource->fields() as $field) {
                    $value = $registry->resolve($itemSourceId.'.'.$field->id, $context);

                    if ($value !== null && $value !== '') {
                        $row[$field->id] = $value;
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
     * @return list<array{key: string, sort?: string, dir?: string, limit?: int}>
     */
    protected function repeatConfigsFromRequest(Request $request, ModelIntegrationRegistry $integrations): array
    {
        $raw = $request->query('repeats');

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $configs = [];

        foreach ($decoded as $config) {
            if (! is_array($config)) {
                continue;
            }

            $key = trim((string) ($config['key'] ?? ''));

            if ($key === '' || $integrations->findByListKey($key) === null) {
                continue;
            }

            $configs[] = [
                'key' => $key,
                'sort' => (string) ($config['sort'] ?? 'id'),
                'dir' => (string) ($config['dir'] ?? 'desc'),
                'limit' => (int) ($config['limit'] ?? 12),
            ];
        }

        return $configs;
    }

    public static function listValuesKey(string $repeatKey, string $sort, string $dir): string
    {
        return $repeatKey.'|'.$sort.'|'.$dir;
    }
}
