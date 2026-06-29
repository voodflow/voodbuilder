<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final class ModelRegistry
{
    /** @var array<string, array{essential: array, relations?: array}> */
    protected array $models = [];

    /** @var array<string, string> */
    protected array $aliases = [];

    public function register(string $modelClass, array $fields, ?string $alias = null): self
    {
        $this->models[$modelClass] = $fields;

        if ($alias) {
            $this->aliases[$modelClass] = $alias;
        }

        return $this;
    }

    public function getFields(string $modelClass): ?array
    {
        return $this->models[$modelClass] ?? null;
    }

    public function hasFields(string $modelClass): bool
    {
        return $this->getFields($modelClass) !== null;
    }

    /**
     * @return array<string, array>
     */
    public function all(): array
    {
        return $this->models;
    }

    public function getAlias(string $modelClass, ?string $default = null): ?string
    {
        return $this->aliases[$modelClass] ?? $default;
    }

    public function forget(string $modelClass): void
    {
        unset($this->models[$modelClass], $this->aliases[$modelClass]);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->models as $class => $fields) {
            $name = $this->aliases[$class] ?? class_basename($class);
            $options[$class] = $name;
        }

        asort($options);

        return $options;
    }
}
