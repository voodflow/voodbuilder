<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Models\BuilderComponent;

final class GrapesJsComponentImporter
{
    /**
     * @param  array<int, mixed>  $rawComponents
     * @return list<BuilderComponent>
     */
    public function import(array $rawComponents): array
    {
        if ($rawComponents === []) {
            throw ValidationException::withMessages([
                'components' => __('voodbuilder::pro.components.import_empty'),
            ]);
        }

        if (count($rawComponents) > 100) {
            throw ValidationException::withMessages([
                'components' => __('voodbuilder::pro.components.import_too_many'),
            ]);
        }

        $created = [];

        foreach ($rawComponents as $index => $raw) {
            if (! is_array($raw)) {
                throw ValidationException::withMessages([
                    "components.{$index}" => __('voodbuilder::pro.components.import_invalid_entry'),
                ]);
            }

            $entry = $this->normalizeEntry($raw, $index);
            $created[] = BuilderComponent::query()->create($entry);
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{name: string, category: ?string, description: ?string, html: string, css: ?string, properties: list<array<string, mixed>>}
     */
    protected function normalizeEntry(array $raw, int $index): array
    {
        $name = trim((string) ($raw['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                "components.{$index}.name" => __('voodbuilder::pro.components.import_name_required'),
            ]);
        }

        if (mb_strlen($name) > 120) {
            throw ValidationException::withMessages([
                "components.{$index}.name" => __('voodbuilder::pro.components.import_name_too_long'),
            ]);
        }

        $html = (string) ($raw['html'] ?? '');

        if (trim($html) === '') {
            throw ValidationException::withMessages([
                "components.{$index}.html" => __('voodbuilder::pro.components.import_html_required'),
            ]);
        }

        $normalized = GrapesJsPastedComponentNormalizer::normalize($html);
        $html = $normalized['html'];

        if (mb_strlen($html) > 200_000) {
            throw ValidationException::withMessages([
                "components.{$index}.html" => __('voodbuilder::pro.components.import_html_too_long'),
            ]);
        }

        $css = GrapesJsPastedComponentNormalizer::mergeCss(
            isset($raw['css']) ? (string) $raw['css'] : null,
            $normalized['css'],
        );

        if ($css !== null && mb_strlen($css) > 50_000) {
            throw ValidationException::withMessages([
                "components.{$index}.css" => __('voodbuilder::pro.components.import_css_too_long'),
            ]);
        }

        $category = isset($raw['category']) ? trim((string) $raw['category']) : null;
        $description = isset($raw['description']) ? trim((string) $raw['description']) : null;

        if ($category !== null && $category !== '' && mb_strlen($category) > 64) {
            throw ValidationException::withMessages([
                "components.{$index}.category" => __('voodbuilder::pro.components.import_category_too_long'),
            ]);
        }

        if ($description !== null && $description !== '' && mb_strlen($description) > 500) {
            throw ValidationException::withMessages([
                "components.{$index}.description" => __('voodbuilder::pro.components.import_description_too_long'),
            ]);
        }

        return [
            'name' => $name,
            'category' => GrapesJsComponentCategoryNormalizer::normalize($category !== '' ? $category : null),
            'description' => $description !== '' ? $description : null,
            'html' => $html,
            'css' => $css !== '' ? $css : null,
            'html_checksum' => GrapesJsPastedComponentNormalizer::htmlChecksum($html),
            'properties' => $this->normalizeProperties($raw['properties'] ?? [], $index),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function normalizeProperties(mixed $properties, int $index): array
    {
        if ($properties === null || $properties === []) {
            return [];
        }

        if (! is_array($properties)) {
            throw ValidationException::withMessages([
                "components.{$index}.properties" => __('voodbuilder::pro.components.import_invalid_properties'),
            ]);
        }

        $normalized = [];

        foreach ($properties as $propertyIndex => $property) {
            if (! is_array($property)) {
                throw ValidationException::withMessages([
                    "components.{$index}.properties.{$propertyIndex}" => __('voodbuilder::pro.components.import_invalid_properties'),
                ]);
            }

            $id = trim((string) ($property['id'] ?? ''));

            if ($id === '' || mb_strlen($id) > 64) {
                throw ValidationException::withMessages([
                    "components.{$index}.properties.{$propertyIndex}.id" => __('voodbuilder::pro.components.import_invalid_properties'),
                ]);
            }

            $label = trim((string) ($property['label'] ?? $id));
            $type = (string) ($property['type'] ?? 'text');

            if (! in_array($type, ['text', 'url', 'image', 'rich_text'], true)) {
                $type = 'text';
            }

            $normalized[] = [
                'id' => $id,
                'label' => mb_substr($label, 0, 120),
                'type' => $type,
                'default' => isset($property['default']) ? (string) $property['default'] : null,
            ];
        }

        return $normalized;
    }
}
