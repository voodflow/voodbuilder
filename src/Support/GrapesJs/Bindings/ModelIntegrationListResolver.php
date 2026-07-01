<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;

final class ModelIntegrationListResolver
{
    public function __construct(
        private readonly ModelIntegrationRegistry $integrations,
        private readonly ModelIntegrationSortFields $sortFields,
    ) {}

    /**
     * @return list<Model>
     */
    public function resolve(
        string $repeatKey,
        int $limit = 6,
        ?string $sort = null,
        ?string $direction = null,
    ): array {
        $integration = $this->integrations->findByListKey($repeatKey);

        if ($integration === null) {
            return [];
        }

        $class = $integration->model_class;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return [];
        }

        $query = $class::query();

        if (method_exists($class, 'scopePublished')) {
            $query->published();
        }

        $column = $this->sortFields->resolveColumn($integration, $sort);
        $dir = $this->sortFields->resolveDirection($direction);

        return $query
            ->orderBy($column, $dir)
            ->limit(max(1, min($limit, 24)))
            ->get()
            ->all();
    }
}
