<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Voodflow\Voodbuilder\Models\ModelIntegration;

/**
 * Model Integration Registry.
 */
final class ModelIntegrationRegistry
{
    /** @var array<string, ModelIntegration> */
    private array $byAlias = [];

    /** @var array<string, ModelIntegration> */
    private array $byListKey = [];

    public function register(ModelIntegration $integration): void
    {
        $alias = $integration->getAlias();

        $this->byAlias[$alias] = $integration;
        $this->byListKey[$alias.'.list'] = $integration;
    }

    public function forget(ModelIntegration $integration): void
    {
        $alias = $integration->getAlias();

        unset($this->byAlias[$alias], $this->byListKey[$alias.'.list']);
    }

    public function findByAlias(string $alias): ?ModelIntegration
    {
        return $this->byAlias[$alias] ?? null;
    }

    public function findByListKey(string $listKey): ?ModelIntegration
    {
        return $this->byListKey[$listKey] ?? null;
    }

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     sortFields: list<array{id: string, label: string}>,
     *     defaultSort: string,
     *     defaultDirection: string,
     *     filters: list<array{id: string, label: string, foreign_key: string, options: list<array{value: string, label: string}>}>
     * }>
     */
    public function repeatCatalog(): array
    {
        if (
            ! class_exists(ModelIntegrationSortFields::class)
            || ! class_exists(ModelIntegrationRelationFilters::class)
        ) {
            return [];
        }

        $items = [];
        $sortFields = app(ModelIntegrationSortFields::class);
        $relationFilters = app(ModelIntegrationRelationFilters::class);

        foreach ($this->byAlias as $alias => $integration) {
            $items[] = [
                'id' => $alias.'.list',
                'label' => $integration->name.' · '.__('voodbuilder::model_integrations.bindings.repeat_source'),
                'sortFields' => $sortFields->forIntegration($integration),
                'defaultSort' => 'id',
                'defaultDirection' => 'desc',
                'filters' => $relationFilters->forIntegration($integration),
            ];
        }

        return $items;
    }

    /**
     * @return list<ModelIntegration>
     */
    public function all(): array
    {
        return array_values($this->byAlias);
    }
}
