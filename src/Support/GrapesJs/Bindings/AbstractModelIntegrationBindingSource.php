<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Contracts\GrapesJsBindingSource;
use Voodflow\Voodbuilder\Models\ModelIntegration;

abstract class AbstractModelIntegrationBindingSource implements GrapesJsBindingSource
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

        return $fields;
    }

    protected function resolveFieldValue(Model $record, string $fieldId, ?BindingContext $context = null): ?string
    {
        $fieldType = ModelIntegrationFieldTypeGuesser::guess($fieldId);
        $bindingContext = $context ?? new BindingContext;

        if ($fieldType === BindingField::TYPE_IMAGE) {
            $fallback = data_get($record, $fieldId);
            $fallbackValue = is_scalar($fallback) && (string) $fallback !== '' ? (string) $fallback : null;

            return BindingMediaUrlResolver::resolve($record, $fieldId, $fallbackValue, $bindingContext);
        }

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
