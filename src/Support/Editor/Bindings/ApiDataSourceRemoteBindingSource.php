<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Models\ApiDataSource;
use Voodflow\Voodbuilder\Support\DataSources\ApiDataSourceManager;

/**
 * Single-record binding from an API / static data source (`{slug}.remote`).
 */
final class ApiDataSourceRemoteBindingSource implements EditorBindingSource
{
    public function __construct(
        private readonly ApiDataSource $source,
    ) {}

    public function id(): string
    {
        return $this->source->slug.'.remote';
    }

    public function label(): string
    {
        return $this->source->name.' · '.__('voodbuilder::api_data_sources.bindings.remote');
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
        $row = app(ApiDataSourceManager::class)->resolvePrimary($this->source);

        if ($row === null) {
            return null;
        }

        if ($fieldId === 'value') {
            return $this->stringify($row['value'] ?? null);
        }

        if ($fieldId === 'label') {
            return $row['label'] !== '' ? $row['label'] : null;
        }

        $meta = is_array($row['meta'] ?? null) ? $row['meta'] : [];
        if (! array_key_exists($fieldId, $meta)) {
            return null;
        }

        return $this->stringify($meta[$fieldId]);
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
