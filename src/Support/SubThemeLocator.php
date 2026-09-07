<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Sub Theme Locator.
 */
final class SubThemeLocator
{
    public static function resolve(string $id): ?SubThemeLocation
    {
        $app = SubThemeLocation::app($id);

        if ($app !== null) {
            return $app;
        }

        $registry = app(SubThemeRegistry::class);

        if (! $registry->exists($id)) {
            return null;
        }

        $cssPath = $registry->cssPath($id);

        if ($cssPath === null) {
            return null;
        }

        if (str_starts_with($cssPath, 'themes/')) {
            $packageThemeId = basename(dirname($cssPath));

            return SubThemeLocation::package($id, $packageThemeId);
        }

        if (str_starts_with($cssPath, 'resources/voodbuilder/themes/')) {
            return SubThemeLocation::app($id);
        }

        // Plugin-owned themes (absolute CSS from vdocs/vtuts/…) are not
        // app-scaffold locations — export/clone still uses registry metadata.
        if (SubThemeCssPath::absolute($cssPath) !== null) {
            return null;
        }

        return null;
    }

    public static function originFor(string $id): string
    {
        $location = self::resolve($id);

        if ($location !== null) {
            return $location->origin;
        }

        if (array_key_exists($id, SubThemeRegistry::packageSubThemeDefinitions())) {
            return 'package';
        }

        return 'unknown';
    }

    /**
     * @return Collection<int, SubThemeLocation>
     */
    public static function exportable(): Collection
    {
        $locations = collect();

        foreach (app(SubThemeRegistry::class)->ids() as $id) {
            $location = self::resolve($id);

            if ($location !== null) {
                $locations->put($id, $location);
            }
        }

        if (is_dir(resource_path('voodbuilder/themes'))) {
            foreach (File::directories(resource_path('voodbuilder/themes')) as $directory) {
                $id = basename($directory);
                $location = SubThemeLocation::app($id);

                if ($location !== null) {
                    $locations->put($id, $location);
                }
            }
        }

        return $locations->values();
    }

    public static function definitionFor(string $id): array
    {
        $registry = app(SubThemeRegistry::class);

        if (! $registry->exists($id)) {
            return [
                'label' => str($id)->headline()->toString(),
                'description' => "Imported {$id} theme.",
                'type' => 'marketing',
                'capabilities' => ['landing'],
            ];
        }

        $definition = [
            'label' => $registry->label($id),
            'description' => $registry->description($id) ?? "Custom {$registry->label($id)} theme.",
            'type' => $registry->type($id)?->value ?? 'marketing',
            'capabilities' => array_map(
                static fn ($capability): string => $capability->value,
                $registry->capabilities($id),
            ),
        ];

        $layouts = [];

        foreach (['home', 'landing', 'page', 'article', 'section_index'] as $layout) {
            $view = $registry->layout($id, $layout);

            if ($view !== null) {
                $layouts[$layout] = $view;
            }
        }

        if ($layouts !== []) {
            $definition['layouts'] = $layouts;
        }

        $cssPath = $registry->cssPath($id);

        if ($cssPath !== null) {
            $definition['css'] = $cssPath;
        }

        $chrome = $registry->chrome($id);

        if ($chrome !== []) {
            $definition['chrome'] = $chrome;
        }

        return $definition;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function appearanceColorsFor(string $id): ?array
    {
        $colors = VoodbuilderSettings::get('sub_theme_colors', []);

        if (is_array($colors[$id] ?? null) && ThemePalette::themeHasCustomColors($id)) {
            return $colors[$id];
        }

        return ThemePalette::paletteFromBundledCss($id);
    }
}
