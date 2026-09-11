<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Models\ApiDataSource;

/**
 * Per-item binding inside an API List repeat (`{slug}.item`).
 */
final class ApiDataSourceItemBindingSource implements EditorBindingSource
{
    public function __construct(
        private readonly ApiDataSource $source,
    ) {}

    public function id(): string
    {
        return $this->source->slug.'.item';
    }

    public function label(): string
    {
        return $this->source->name.' · '.__('voodbuilder::api_data_sources.bindings.list_item');
    }

    public function package(): string
    {
        return 'api-data-sources';
    }

    public function packageLabel(): string
    {
        return __('voodbuilder::api_data_sources.bindings.package');
    }

    public function fields(): array
    {
        $fields = [];

        foreach ($this->source->bindableFieldIds() as $fieldId) {
            $fields[] = new BindingField(
                id: $fieldId,
                label: $this->fieldLabel($fieldId),
                type: $this->guessType($fieldId),
            );
        }

        return $fields;
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        $record = $context->repeatItem;

        if (! $record instanceof ApiDataSourceRow) {
            return null;
        }

        if ($record->sourceSlug !== $this->source->slug) {
            return null;
        }

        $value = $record->getAttribute($fieldId);

        return $this->stringify($value);
    }

    private function fieldLabel(string $fieldId): string
    {
        return match ($fieldId) {
            'value' => __('voodbuilder::api_data_sources.bindings.fields.value'),
            'label' => __('voodbuilder::api_data_sources.bindings.fields.label'),
            default => str_replace('_', ' ', ucfirst($fieldId)),
        };
    }

    private function guessType(string $fieldId): string
    {
        $lower = strtolower($fieldId);

        if (str_contains($lower, 'url') || str_contains($lower, 'href') || str_contains($lower, 'link')) {
            return BindingField::TYPE_URL;
        }

        if (str_contains($lower, 'image') || str_contains($lower, 'avatar') || str_contains($lower, 'photo') || str_contains($lower, 'thumb')) {
            return BindingField::TYPE_IMAGE;
        }

        return BindingField::TYPE_TEXT;
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            $string = (string) $value;

            return $string === '' ? null : $string;
        }

        return null;
    }
}
