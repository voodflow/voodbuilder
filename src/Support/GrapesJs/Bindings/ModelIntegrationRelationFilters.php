<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Models\ModelIntegration;

/**
 * BelongsTo relation filters for List repeat (e.g. filter tutorials by category).
 */
final class ModelIntegrationRelationFilters
{
    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     foreign_key: string,
     *     options: list<array{value: string, label: string}>
     * }>
     */
    public function forIntegration(ModelIntegration $integration): array
    {
        $class = $integration->model_class;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return [];
        }

        $filters = [];

        foreach ($integration->getNormalizedFields()['relations'] ?? [] as $relationName => $relation) {
            if (! is_string($relationName) || $relationName === '') {
                continue;
            }

            $model = new $class;

            if (! method_exists($model, $relationName)) {
                continue;
            }

            $relationInstance = $model->{$relationName}();

            if (! $relationInstance instanceof BelongsTo) {
                continue;
            }

            $relatedClass = $relationInstance->getRelated()::class;
            $foreignKey = $relationInstance->getForeignKeyName();
            $alias = filled($relation['alias'] ?? null)
                ? (string) $relation['alias']
                : $relationName;
            $labelField = $this->guessLabelField($relation['fields'] ?? []);

            $filters[] = [
                'id' => $alias,
                'label' => __('voodbuilder::model_integrations.bindings.filter_by', [
                    'name' => Str::headline($alias),
                ]),
                'foreign_key' => $foreignKey,
                'options' => $this->optionsForRelated($relatedClass, $labelField),
            ];
        }

        return $filters;
    }

    /**
     * @param  array<int|string, string>  $fields
     */
    private function guessLabelField(array $fields): string
    {
        $candidates = [];

        foreach ($fields as $key => $label) {
            $candidates[] = is_int($key) ? (string) $label : (string) $key;
        }

        foreach (['name', 'title', 'label', 'slug'] as $preferred) {
            if (in_array($preferred, $candidates, true)) {
                return $preferred;
            }
        }

        return $candidates[0] ?? 'id';
    }

    /**
     * @param  class-string<Model>  $relatedClass
     * @return list<array{value: string, label: string}>
     */
    private function optionsForRelated(string $relatedClass, string $labelField): array
    {
        if (! class_exists($relatedClass) || ! is_subclass_of($relatedClass, Model::class)) {
            return [];
        }

        $query = $relatedClass::query();

        if (method_exists($relatedClass, 'scopePublished')) {
            $query->published();
        }

        $rows = $query
            ->orderBy($labelField)
            ->limit(200)
            ->get(['id', $labelField]);

        $options = [];

        foreach ($rows as $row) {
            $label = data_get($row, $labelField);

            $options[] = [
                'value' => (string) $row->getKey(),
                'label' => is_scalar($label) && (string) $label !== ''
                    ? (string) $label
                    : '#'.$row->getKey(),
            ];
        }

        return $options;
    }
}
