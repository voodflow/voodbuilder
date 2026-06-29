<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Vpress\Models\ModelIntegration;

final class ModelIntegrationLatestBindingSource extends AbstractModelIntegrationBindingSource
{
    public function id(): string
    {
        return $this->integration->getAlias().'.latest';
    }

    public function label(): string
    {
        return $this->integration->name.' · '.__('vpress::model_integrations.bindings.latest');
    }

    public function fields(): array
    {
        return $this->fieldsForEssentialKeys();
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        $record = $this->resolveLatestRecord();

        if (! $record instanceof Model) {
            return null;
        }

        return $this->resolveFieldValue($record, $fieldId);
    }

    protected function resolveLatestRecord(): ?Model
    {
        $class = $this->integration->model_class;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        $query = $class::query();

        if (method_exists($class, 'scopePublished')) {
            $query->published();
        }

        return $query->latest('id')->first();
    }
}
