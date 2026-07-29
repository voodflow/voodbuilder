<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Models\ModelIntegration;

abstract class AbstractModelIntegrationBindingSource implements EditorBindingSource
{
    public function __construct(
        protected readonly ModelIntegration $integration,
    ) {}

    public function package(): string
    {
        return 'integrations';
    }

    public function packageLabel(): string
    {
        return __('voodbuilder::model_integrations.bindings.package');
    }

    public function label(): string
    {
        return $this->integration->name;
    }

    /**
     * @return list<BindingField>
     */
    protected function fieldsForEssentialKeys(): array
    {
        $fields = [];
        $definition = $this->integration->getNormalizedFields();

        foreach ($definition['essential'] as $key => $label) {
            if (is_int($key)) {
                $fieldId = (string) $label;
                $fieldLabel = str_replace('_', ' ', ucfirst($fieldId));
            } else {
                $fieldId = (string) $key;
                $fieldLabel = (string) $label;
            }

            $fields[] = new BindingField(
                id: $fieldId,
                label: $fieldLabel,
                type: ModelIntegrationFieldTypeGuesser::guess($fieldId),
            );
        }

        return array_merge($fields, $this->fieldsForRelationKeys($definition));
    }

    /**
     * @param  array{essential: array, relations?: array<string, array{alias?: ?string, fields: array, expand?: list<string>, expand_fields?: array<string, list<string>>}>}  $definition
     * @return list<BindingField>
     */
    protected function fieldsForRelationKeys(array $definition): array
    {
        $fields = [];

        foreach ($definition['relations'] ?? [] as $relationName => $relation) {
            if (! is_array($relation)) {
                continue;
            }

            $alias = filled($relation['alias'] ?? null)
                ? (string) $relation['alias']
                : (string) $relationName;
            $groupLabel = __('voodbuilder::model_integrations.bindings.relation_group', [
                'name' => Str::headline($alias),
            ]);

            foreach ($relation['fields'] ?? [] as $key => $label) {
                if (is_int($key)) {
                    $fieldId = (string) $label;
                    $fieldLabel = str_replace('_', ' ', ucfirst($fieldId));
                } else {
                    $fieldId = (string) $key;
                    $fieldLabel = (string) $label;
                }

                $fields[] = new BindingField(
                    id: $alias.'.'.$fieldId,
                    label: $fieldLabel,
                    type: ModelIntegrationFieldTypeGuesser::guess($fieldId),
                    group: $groupLabel,
                );
            }

            foreach ($relation['expand_fields'] ?? [] as $nestedRelation => $nestedFields) {
                if (! is_string($nestedRelation) || $nestedRelation === '') {
                    continue;
                }

                $nestedGroup = __('voodbuilder::model_integrations.bindings.relation_group', [
                    'name' => Str::headline($alias.' › '.$nestedRelation),
                ]);

                foreach ($nestedFields as $nestedField) {
                    $nestedFieldId = (string) $nestedField;

                    if ($nestedFieldId === '') {
                        continue;
                    }

                    $fields[] = new BindingField(
                        id: $alias.'.'.$nestedRelation.'.'.$nestedFieldId,
                        label: str_replace('_', ' ', ucfirst($nestedFieldId)),
                        type: ModelIntegrationFieldTypeGuesser::guess($nestedFieldId),
                        group: $nestedGroup,
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * @return list<string>
     */
    protected function relationEagerLoads(): array
    {
        $loads = [];
        $definition = $this->integration->getNormalizedFields();

        foreach ($definition['relations'] ?? [] as $relationName => $relation) {
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

        return array_values(array_unique($loads));
    }

    protected function resolveFieldValue(Model $record, string $fieldId, ?BindingContext $context = null): ?string
    {
        $leaf = Str::afterLast($fieldId, '.');
        $fieldType = ModelIntegrationFieldTypeGuesser::guess($leaf !== '' ? $leaf : $fieldId);
        $bindingContext = $context ?? new BindingContext;

        if ($fieldType === BindingField::TYPE_IMAGE) {
            $fallback = data_get($record, $fieldId);
            $fallbackValue = is_scalar($fallback) && (string) $fallback !== '' ? (string) $fallback : null;

            return BindingMediaUrlResolver::resolve($record, $fieldId, $fallbackValue, $bindingContext);
        }

        if ($fieldType === BindingField::TYPE_URL) {
            if (! str_contains($fieldId, '.')) {
                return BindingUrlResolver::resolve($record, $fieldId);
            }

            $related = data_get($record, Str::beforeLast($fieldId, '.'));

            if ($related instanceof Model) {
                return BindingUrlResolver::resolve($related, $leaf);
            }
        }

        if (! str_contains($fieldId, '.')) {
            $urlAccessor = Str::camel($fieldId).'Url';

            if (method_exists($record, $urlAccessor)) {
                $url = $record->{$urlAccessor}();

                if (is_string($url) && $url !== '') {
                    return $url;
                }

                if ($url === null) {
                    return null;
                }
            }
        }

        $value = data_get($record, $fieldId);

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return null;
    }
}
