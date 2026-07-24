<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;

final class ModelIntegrationListResolver
{
    public function __construct(
        private readonly ModelIntegrationRegistry $integrations,
        private readonly ModelIntegrationSortFields $sortFields,
        private readonly RepeatListRegistry $repeatLists,
    ) {}

    /**
     * @param  array<string, string>  $filters  Relation alias => related id (e.g. ['category' => '3'])
     * @return list<Model>
     */
    public function resolve(
        string $repeatKey,
        int $limit = 6,
        ?string $sort = null,
        ?string $direction = null,
        int $offset = 0,
        array $filters = [],
    ): array {
        if ($this->repeatLists->has($repeatKey)) {
            return $this->repeatLists->resolve($repeatKey, $limit, $sort, $direction, $offset);
        }

        $integration = $this->integrations->findByListKey($repeatKey);

        if ($integration === null) {
            return [];
        }

        $class = $integration->model_class;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return [];
        }

        $query = $class::query();
        $this->applyListingScope($query, $class);
        $this->applyRelationEagerLoads($query, $integration);
        $this->applyRelationFilters($query, $integration, $filters);

        $column = $this->sortFields->resolveColumn($integration, $sort);
        $dir = $this->sortFields->resolveDirection($direction);
        $skip = max(0, min($offset, 100));

        return $query
            ->orderBy($column, $dir)
            ->offset($skip)
            ->limit(max(1, min($limit, 24)))
            ->get()
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, string>  $filters
     */
    private function applyRelationFilters(mixed $query, \Voodflow\Voodbuilder\Models\ModelIntegration $integration, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        $definitions = app(ModelIntegrationRelationFilters::class)->forIntegration($integration);
        $byId = [];

        foreach ($definitions as $definition) {
            $byId[$definition['id']] = $definition;
        }

        foreach ($filters as $filterId => $value) {
            $filterId = (string) $filterId;
            $value = trim((string) $value);

            if ($filterId === '' || $value === '' || ! isset($byId[$filterId])) {
                continue;
            }

            $foreignKey = $byId[$filterId]['foreign_key'];
            $query->where($foreignKey, $value);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyRelationEagerLoads(mixed $query, \Voodflow\Voodbuilder\Models\ModelIntegration $integration): void
    {
        $loads = [];

        foreach ($integration->getNormalizedFields()['relations'] ?? [] as $relationName => $relation) {
            if (! is_string($relationName) || $relationName === '') {
                continue;
            }

            $loads[] = $relationName;

            foreach ($relation['expand'] ?? [] as $nested) {
                if (is_string($nested) && $nested !== '') {
                    $loads[] = $relationName.'.'.$nested;
                }
            }
        }

        if ($loads !== []) {
            $query->with(array_values(array_unique($loads)));
        }
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function applyListingScope(mixed $query, string $class): void
    {
        if ($this->modelHasQueryScope($class, 'publiclyListed')) {
            $query->publiclyListed();

            return;
        }

        if ($this->modelHasQueryScope($class, 'published')) {
            $query->published();
        }
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function modelHasQueryScope(string $class, string $scope): bool
    {
        return method_exists($class, 'scope'.ucfirst($scope))
            || (new \ReflectionClass($class))->hasMethod($scope);
    }
}
