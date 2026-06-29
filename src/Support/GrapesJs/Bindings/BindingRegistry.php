<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

use Voodflow\Vpress\Contracts\GrapesJsBindingSource;

final class BindingRegistry
{
    /** @var array<string, GrapesJsBindingSource> */
    private array $sources = [];

    public function register(GrapesJsBindingSource $source): self
    {
        $this->sources[$source->id()] = $source;

        return $this;
    }

    public function has(string $sourceId): bool
    {
        return array_key_exists($sourceId, $this->sources);
    }

    public function source(string $sourceId): ?GrapesJsBindingSource
    {
        return $this->sources[$sourceId] ?? null;
    }

    public function hasField(string $sourceId, string $fieldId): bool
    {
        if ($this->field($sourceId, $fieldId) !== null) {
            return true;
        }

        $source = $this->source($sourceId);

        if ($source === null) {
            return false;
        }

        if (method_exists($source, 'legacyFieldIds')) {
            return in_array($fieldId, $source->legacyFieldIds(), true);
        }

        return false;
    }

    public function field(string $sourceId, string $fieldId): ?BindingField
    {
        $source = $this->sources[$sourceId] ?? null;

        if ($source === null) {
            return null;
        }

        if (method_exists($source, 'legacyFieldIds') && in_array($fieldId, $source->legacyFieldIds(), true)) {
            $fieldId = 'introduction';
        }

        foreach ($source->fields() as $field) {
            if ($field->id === $fieldId) {
                return $field;
            }
        }

        return null;
    }

    public function resolve(string $bindingKey, BindingContext $context): ?string
    {
        $parsed = BindingKey::tryParse($bindingKey, $this);

        if ($parsed === null) {
            return null;
        }

        $source = $this->sources[$parsed->sourceId] ?? null;

        if ($source === null) {
            return null;
        }

        return $source->resolve($parsed->fieldId, $context);
    }

    /**
     * @return list<string>
     */
    public function sourceIdsByLengthDesc(): array
    {
        $ids = array_keys($this->sources);

        usort($ids, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $ids;
    }

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     package: string,
     *     package_label: string,
     *     fields: list<array{id: string, label: string, type: string}>
     * }>
     */
    public function catalog(): array
    {
        $catalog = [];

        foreach ($this->sources as $source) {
            $catalog[] = [
                'id' => $source->id(),
                'label' => $source->label(),
                'package' => $source->package(),
                'package_label' => $source->packageLabel(),
                'fields' => array_map(
                    static fn (BindingField $field): array => $field->toArray(),
                    $source->fields(),
                ),
            ];
        }

        usort(
            $catalog,
            static fn (array $a, array $b): int => [$a['package_label'], $a['label']] <=> [$b['package_label'], $b['label']],
        );

        return $catalog;
    }

    /**
     * @return list<array{package: string, package_label: string, sources: list<array<string, mixed>>}>
     */
    public function catalogGroupedByPackage(): array
    {
        $groups = [];

        foreach ($this->catalog() as $source) {
            $package = $source['package'];

            if (! isset($groups[$package])) {
                $groups[$package] = [
                    'package' => $package,
                    'package_label' => $source['package_label'],
                    'sources' => [],
                ];
            }

            $groups[$package]['sources'][] = $source;
        }

        return array_values($groups);
    }
}
