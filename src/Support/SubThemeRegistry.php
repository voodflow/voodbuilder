<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

final class SubThemeRegistry
{
    /** @var array<string, array{label: string, description?: string, layouts?: array<string, string>, css?: string}> */
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
     * @param  array{label?: string, description?: string, layouts?: array<string, string>, css?: string}  $definition
     */
    public function register(string $id, array $definition): self
    {
        $this->themes[$id] = array_merge([
            'label' => str($id)->headline()->toString(),
            'description' => null,
            'layouts' => [],
            'css' => null,
        ], $definition);

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
        $options = [];

        foreach ($this->themes as $id => $theme) {
            $options[$id] = (string) ($theme['label'] ?? $id);
        }

        return $options;
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
}
