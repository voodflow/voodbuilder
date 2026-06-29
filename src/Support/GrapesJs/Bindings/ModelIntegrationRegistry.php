<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

use Voodflow\Vpress\Models\ModelIntegration;

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
     * @return list<array{id: string, label: string, sortFields: list<array{id: string, label: string}>, defaultSort: string, defaultDirection: string}>
     */
    public function repeatCatalog(): array
    {
        $items = [];
        $sortFields = app(ModelIntegrationSortFields::class);

        foreach ($this->byAlias as $alias => $integration) {
            $items[] = [
                'id' => $alias.'.list',
                'label' => $integration->name.' · '.__('vpress::model_integrations.bindings.repeat_source'),
                'sortFields' => $sortFields->forIntegration($integration),
                'defaultSort' => 'id',
                'defaultDirection' => 'desc',
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
