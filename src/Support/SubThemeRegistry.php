<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Enums\SubThemeType;

final class SubThemeRegistry
{
    /** @var array<string, array{label: string, description?: string, type?: SubThemeType|string, layouts?: array<string, string>, css?: string}> */
    private array $themes = [];

    public function bootFromConfig(): void
    {
        foreach (config('vpress.sub_themes', []) as $id => $definition) {
            if (! is_string($id) || ! is_array($definition)) {
                continue;
            }

            $this->register($id, $definition);
        }
    }

    /**
     * @param  array{label?: string, description?: string, type?: SubThemeType|string, layouts?: array<string, string>, css?: string}  $definition
     */
    public function register(string $id, array $definition): self
    {
        $merged = array_merge([
            'label' => str($id)->headline()->toString(),
            'description' => null,
            'type' => SubThemeType::Marketing,
            'layouts' => [],
            'css' => null,
        ], $definition);

        $merged['type'] = SubThemeType::fromDefinition($merged);

        $this->themes[$id] = $merged;

        return $this;
    }

    public function exists(string $id): bool
    {
        return array_key_exists($id, $this->themes);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return $this->optionsForIds($this->ids());
    }

    /**
     * @return array<string, string>
     */
    public function optionsByType(SubThemeType $type): array
    {
        return $this->optionsForIds($this->idsByType($type));
    }

    /**
     * @return array<string, string>
     */
    public function marketingOptions(?string $includeId = null): array
    {
        return $this->optionsByTypeWithLegacy(SubThemeType::Marketing, $includeId);
    }

    /**
     * @return array<string, string>
     */
    public function contentOptions(?string $includeId = null): array
    {
        return $this->optionsByTypeWithLegacy(SubThemeType::Content, $includeId);
    }

    public function type(string $id): ?SubThemeType
    {
        if (! $this->exists($id)) {
            return null;
        }

        return $this->themes[$id]['type'] ?? null;
    }

    /**
     * @return list<string>
     */
    public function idsByType(SubThemeType $type): array
    {
        return array_values(array_filter(
            $this->ids(),
            fn (string $id): bool => $this->type($id) === $type,
        ));
    }

    public function label(string $id): string
    {
        return (string) ($this->themes[$id]['label'] ?? $id);
    }

    public function description(string $id): ?string
    {
        $description = $this->themes[$id]['description'] ?? null;

        return is_string($description) && $description !== '' ? $description : null;
    }

    public function layout(string $themeId, string $layout): ?string
    {
        if (! $this->exists($themeId)) {
            return null;
        }

        $layouts = $this->themes[$themeId]['layouts'] ?? [];

        if (! is_array($layouts)) {
            return null;
        }

        $view = $layouts[$layout] ?? null;

        return is_string($view) && $view !== '' ? $view : null;
    }

    public function cssPath(string $themeId): ?string
    {
        if (! $this->exists($themeId)) {
            return null;
        }

        $css = $this->themes[$themeId]['css'] ?? null;

        return is_string($css) && $css !== '' ? $css : null;
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->themes);
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private function optionsForIds(array $ids): array
    {
        $options = [];

        foreach ($ids as $id) {
            if (! $this->exists($id)) {
                continue;
            }

            $options[$id] = (string) ($this->themes[$id]['label'] ?? $id);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function optionsByTypeWithLegacy(SubThemeType $type, ?string $includeId): array
    {
        $options = $this->optionsByType($type);

        if ($includeId !== null && $includeId !== '' && $this->exists($includeId) && ! array_key_exists($includeId, $options)) {
            $options[$includeId] = $this->label($includeId);
        }

        return $options;
    }
}
