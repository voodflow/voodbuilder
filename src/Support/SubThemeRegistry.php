<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Enums\SubThemeCapability;
use Voodflow\Voodbuilder\Enums\SubThemeType;

/**
 * Sub Theme Registry.
 */
final class SubThemeRegistry
{
    /** @var array<string, array{label: string, description?: string, type?: SubThemeType, capabilities?: list<SubThemeCapability>, layouts?: array<string, string>, css?: string}> */
    private array $themes = [];

    public function bootFromConfig(): void
    {
        foreach (self::packageSubThemeDefinitions() as $id => $definition) {
            if (! is_string($id) || ! is_array($definition)) {
                continue;
            }

            $this->register($id, $definition);
        }

        foreach (config('voodbuilder.sub_themes', []) as $id => $definition) {
            if (! is_string($id) || ! is_array($definition)) {
                continue;
            }

            $this->register($id, $definition);
        }
    }

    /**
     * Bundled themes from the package config. The host app's published
     * `config/voodbuilder.php` replaces merged config, so app `sub_themes` must not
     * be the only source — custom themes are merged on top of these defaults.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function packageSubThemeDefinitions(): array
    {
        $path = VoodbuilderPaths::packagePath().'/config/voodbuilder.php';

        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, mixed> $config */
        $config = require $path;
        $themes = $config['sub_themes'] ?? [];

        return is_array($themes) ? $themes : [];
    }

    /**
     * @param  array{label?: string, description?: string, type?: SubThemeType|string, capabilities?: list<string|SubThemeCapability>, layouts?: array<string, string>, css?: string}  $definition
     */
    public function register(string $id, array $definition): self
    {
        $type = SubThemeType::fromDefinition($definition);
        $capabilities = $this->resolveCapabilities($definition, $type);

        $merged = array_merge([
            'label' => str($id)->headline()->toString(),
            'description' => null,
            'type' => $type,
            'capabilities' => $capabilities,
            'layouts' => [],
            'css' => null,
        ], $definition);

        $merged['type'] = $type;
        $merged['capabilities'] = $capabilities;

        $this->themes[$id] = $merged;

        return $this;
    }

    public function unregister(string $id): void
    {
        unset($this->themes[$id]);
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
    public function optionsForCapability(SubThemeCapability $capability, ?string $includeId = null): array
    {
        $ids = array_values(array_filter(
            $this->ids(),
            fn (string $id): bool => $this->supportsCapability($id, $capability),
        ));

        if ($includeId !== null && $includeId !== '' && $this->exists($includeId) && ! in_array($includeId, $ids, true)) {
            $ids[] = $includeId;
        }

        return $this->optionsForIds($ids);
    }

    /**
     * @return array<string, string>
     */
    public function marketingOptions(?string $includeId = null): array
    {
        return $this->optionsForCapability(SubThemeCapability::Landing, $includeId);
    }

    /**
     * @return array<string, string>
     */
    public function contentOptions(?string $includeId = null): array
    {
        $options = $this->optionsForCapability(SubThemeCapability::Doc, $includeId);

        foreach ($this->optionsForCapability(SubThemeCapability::Article, $includeId) as $id => $label) {
            $options[$id] ??= $label;
        }

        return $options;
    }

    /**
     * @return list<SubThemeCapability>
     */
    public function capabilities(string $id): array
    {
        if (! $this->exists($id)) {
            return [];
        }

        return $this->themes[$id]['capabilities'] ?? [];
    }

    public function supportsCapability(string $id, SubThemeCapability $capability): bool
    {
        return in_array($capability, $this->capabilities($id), true);
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

    /**
     * @param  list<string>  $alternates
     */
    public function resolveLayout(string $themeId, string $layout, array $alternates = []): ?string
    {
        foreach ([$layout, ...$alternates] as $candidate) {
            $view = $this->layout($themeId, $candidate);

            if ($view !== null && view()->exists($view)) {
                return $view;
            }
        }

        return null;
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
     * @return array{hide_site_nav?: bool, hide_site_footer?: bool}
     */
    public function chrome(string $id): array
    {
        if (! $this->exists($id)) {
            return [];
        }

        $chrome = $this->themes[$id]['chrome'] ?? [];

        return is_array($chrome) ? $chrome : [];
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
     * @param  array{capabilities?: list<string|SubThemeCapability>, type?: SubThemeType|string, layouts?: array<string, string>}  $definition
     * @return list<SubThemeCapability>
     */
    private function resolveCapabilities(array $definition, SubThemeType $type): array
    {
        if (isset($definition['capabilities']) && is_array($definition['capabilities'])) {
            $parsed = SubThemeCapability::parseList($definition['capabilities']);

            if ($parsed !== []) {
                return $parsed;
            }
        }

        $fromLayouts = $this->capabilitiesFromLayouts($definition['layouts'] ?? []);

        if ($fromLayouts !== []) {
            return $fromLayouts;
        }

        return SubThemeCapability::fromLegacyType($type);
    }

    /**
     * @param  array<string, string>  $layouts
     * @return list<SubThemeCapability>
     */
    private function capabilitiesFromLayouts(array $layouts): array
    {
        $capabilities = [];

        if (array_key_exists('landing', $layouts) || array_key_exists('home', $layouts)) {
            $capabilities[SubThemeCapability::Landing->value] = SubThemeCapability::Landing;
        }

        if (array_key_exists('article', $layouts) || array_key_exists('section_index', $layouts)) {
            $capabilities[SubThemeCapability::Article->value] = SubThemeCapability::Article;
        }

        if ($layouts === [] || array_key_exists('page', $layouts)) {
            $capabilities[SubThemeCapability::Doc->value] = SubThemeCapability::Doc;
        }

        return array_values($capabilities);
    }
}
