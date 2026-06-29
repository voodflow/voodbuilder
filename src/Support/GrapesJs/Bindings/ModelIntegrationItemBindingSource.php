<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;

final class ModelIntegrationItemBindingSource extends AbstractModelIntegrationBindingSource
{
    public function id(): string
    {
        return $this->integration->getAlias().'.item';
    }

    public function label(): string
    {
        return $this->integration->name.' · '.__('vpress::model_integrations.bindings.list_item');
    }

    public function fields(): array
    {
        return $this->fieldsForEssentialKeys();
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        $record = $context->repeatItem;

        if (! $record instanceof Model) {
            return null;
        }

        if ($record::class !== $this->integration->model_class) {
            return null;
        }

        return $this->resolveFieldValue($record, $fieldId);
    }
}
