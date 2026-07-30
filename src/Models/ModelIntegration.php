<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ModelIntegrationBindingRegistrar;
use Voodflow\Voodbuilder\Support\ModelRegistry;

/**
 * Model Integration.
 */
class ModelIntegration extends Model
{
    use SoftDeletes;

    protected $table = 'voodbuilder_model_integrations';

    protected $fillable = [
        'name',
        'model_class',
        'model_alias',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $integration) => $integration->refreshRegistrations());
        static::deleted(fn (self $integration) => $integration->unregister());
        static::forceDeleted(fn (self $integration) => $integration->unregister());
    }

    public function refreshRegistrations(): void
    {
        app(ModelIntegrationBindingRegistrar::class)->unregister($this);
        $this->unregister();
        $this->registerOnBoot();
        app(ModelIntegrationBindingRegistrar::class)->register($this);
    }

    public function registerOnBoot(): void
    {
        $fields = $this->getNormalizedFields();

        if ($fields !== []) {
            app(ModelRegistry::class)->register($this->model_class, $fields, $this->getAlias());
        }
    }

    public function unregister(): void
    {
        app(ModelRegistry::class)->forget($this->model_class);
    }

    public function getAlias(): string
    {
        if ($this->model_alias) {
            return $this->model_alias;
        }

        return Str::camel(class_basename($this->model_class));
    }

    /**
     * @return array{essential: array, relations?: array, reverse_relations?: array}
     */
    public function getNormalizedFields(): array
    {
        $definition = [
            'essential' => [],
            'relations' => [],
            'reverse_relations' => [],
        ];

        foreach (Arr::get($this->fields, 'essential', []) as $field) {
            if (is_string($field)) {
                $definition['essential'][] = $field;

                continue;
            }

            $fieldName = $field['field'] ?? null;

            if (! $fieldName) {
                continue;
            }

            $label = $field['label'] ?? null;

            if ($label) {
                $definition['essential'][$fieldName] = $label;
            } else {
                $definition['essential'][] = $fieldName;
            }
        }

        foreach (Arr::get($this->fields, 'relations', []) as $relation) {
            $mode = $relation['relation_mode'] ?? 'direct';

            if ($mode === 'reverse') {
                $descriptor = $relation['relation_descriptor'] ?? null;

                if (! $descriptor) {
                    continue;
                }

                $definition['reverse_relations'][] = [
                    'descriptor' => $descriptor,
                    'alias' => $relation['alias'] ?? null,
                    'fields' => $this->normalizeRelationFields($relation['fields'] ?? []),
                    'expand' => array_values(array_filter($relation['expand'] ?? [])),
                ];

                continue;
            }

            $name = $relation['name'] ?? null;

            if (! $name) {
                continue;
            }

            $expand = [];
            $expandFields = [];

            foreach ($relation['nested_relations'] ?? [] as $nestedRelation) {
                $nestedRel = $nestedRelation['relation'] ?? null;
                $nestedFields = array_values(array_filter($nestedRelation['fields'] ?? []));

                if ($nestedRel) {
                    $expand[] = $nestedRel;

                    if ($nestedFields !== []) {
                        $expandFields[$nestedRel] = $nestedFields;
                    }
                }
            }

            $definition['relations'][$name] = [
                'alias' => $relation['alias'] ?? null,
                'fields' => $this->normalizeRelationFields($relation['fields'] ?? []),
                'expand' => $expand,
                'expand_fields' => $expandFields,
            ];
        }

        return $definition;
    }

    /**
     * @param  array<int, array{field?: string|null, label?: string|null}|string>  $fields
     * @return array<int|string, string>
     */
    protected function normalizeRelationFields(array $fields): array
    {
        $relationFields = [];

        foreach ($fields as $field) {
            if (is_string($field)) {
                $relationFields[] = $field;

                continue;
            }

            $fieldName = $field['field'] ?? null;

            if (! $fieldName) {
                continue;
            }

            $label = $field['label'] ?? null;

            if ($label) {
                $relationFields[$fieldName] = $label;
            } else {
                $relationFields[] = $fieldName;
            }
        }

        return $relationFields;
    }
}
