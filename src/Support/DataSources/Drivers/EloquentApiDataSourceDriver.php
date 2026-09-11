<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources\Drivers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\DataSources\Contracts\ApiDataSourceDriverInterface;
use Voodflow\Voodbuilder\Support\DataSources\Contracts\SuggestsMetaKeys;

/**
 * Resolve rows from a VoodBuilder Model Integration (Eloquent allowlist).
 *
 * Config: model_integration_id, value_column, label_column, search_columns[],
 * meta_columns{} (key → column), filters{}, limit.
 */
final class EloquentApiDataSourceDriver implements ApiDataSourceDriverInterface, SuggestsMetaKeys
{
    public function suggestMetaKeys(array $config = []): array
    {
        $meta = $config['meta_columns'] ?? null;
        if (! is_array($meta)) {
            return [];
        }

        return array_values(array_filter(
            array_map('strval', array_keys($meta)),
            static fn (string $key): bool => $key !== '',
        ));
    }

    public function resolve(array $config, array $params = []): array
    {
        $integration = $this->findIntegration($config);
        if ($integration === null) {
            return [];
        }

        $class = $integration->model_class;
        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return [];
        }

        $valueColumn = (string) ($config['value_column'] ?? 'id');
        $labelColumn = (string) ($config['label_column'] ?? $valueColumn);
        $searchColumns = $config['search_columns'] ?? null;
        if (is_string($searchColumns)) {
            $searchColumns = array_values(array_filter(array_map('trim', explode(',', $searchColumns))));
        }
        if (! is_array($searchColumns) || $searchColumns === []) {
            $searchColumns = [$labelColumn, $valueColumn];
        } else {
            $searchColumns = array_values(array_filter(array_map('strval', $searchColumns)));
        }
        $metaColumns = is_array($config['meta_columns'] ?? null) ? $config['meta_columns'] : [];
        $filters = is_array($config['filters'] ?? null) ? $config['filters'] : [];

        /** @var Builder<Model> $query */
        $query = $class::query();

        foreach ($filters as $column => $value) {
            $query->where((string) $column, $value);
        }

        if (isset($params['value'])) {
            $query->where($valueColumn, $params['value']);
        }

        if (isset($params['query']) && filled($params['query'])) {
            $term = (string) $params['query'];
            $query->where(function (Builder $q) use ($searchColumns, $term): void {
                foreach ($searchColumns as $i => $column) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->{$method}((string) $column, 'like', '%'.$term.'%');
                }
            });
        }

        $limit = (int) ($params['limit'] ?? $config['limit'] ?? 25);
        $limit = max(1, min($limit, 100));

        return $query->limit($limit)->get()->map(function (Model $model) use ($valueColumn, $labelColumn, $metaColumns): array {
            $meta = [];
            foreach ($metaColumns as $key => $column) {
                $meta[(string) $key] = $model->getAttribute((string) $column);
            }

            return [
                'value' => $model->getAttribute($valueColumn),
                'label' => (string) ($model->getAttribute($labelColumn) ?? $model->getAttribute($valueColumn) ?? ''),
                'meta' => $meta,
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function findIntegration(array $config): ?ModelIntegration
    {
        $id = $config['model_integration_id'] ?? null;
        if ($id === null || $id === '') {
            return null;
        }

        try {
            return ModelIntegration::query()->find($id);
        } catch (\Throwable) {
            return null;
        }
    }
}
