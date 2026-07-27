<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

final class ThemePresenter
{
    /** @var list<string> */
    public const COLOR_KEYS = [
        'primary',
        'secondary',
        'header_bg',
        'header_text',
        'body_bg',
        'text',
    ];

    /**
     * @return array{custom: list<array<string, mixed>>, plugin: list<array<string, mixed>>}
     */
    public static function groupedCards(): array
    {
        $custom = [];
        $plugin = [];

        foreach (app(SubThemeRegistry::class)->ids() as $id) {
            $card = self::card($id);

            if ($card['is_app']) {
                $custom[] = $card;
            } else {
                $plugin[] = $card;
            }
        }

        return [
            'custom' => $custom,
            'plugin' => $plugin,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(string $id): array
    {
        $registry = app(SubThemeRegistry::class);
        $location = SubThemeLocator::resolve($id);
        $definition = SubThemeLocator::definitionFor($id);

        return [
            'id' => $id,
            'label' => $registry->label($id),
            'description' => is_array($definition) ? (string) ($definition['description'] ?? '') : '',
            'preview' => self::previewColor($id),
            'surface' => self::cardSurface($id),
            'strip' => self::stripHexes(self::modeColors($id, 'light')),
            'is_app' => $location?->isApp() ?? false,
            'can_delete' => $location?->isApp() ?? false,
            'can_edit_meta' => $location?->isApp() ?? false,
            'can_edit_colors' => $location?->isApp() ?? false,
            'can_export' => $location !== null,
            'has_custom_colors' => ThemePalette::themeHasCustomColors($id),
            'type' => is_array($definition) && isset($definition['type'])
                ? (is_object($definition['type']) ? $definition['type']->value : (string) $definition['type'])
                : 'marketing',
        ];
    }

    public static function colorLabel(string $key): string
    {
        $langKey = $key === 'text' ? 'theme_body_text' : 'theme_'.$key;

        return (string) __('voodbuilder::settings.'.$langKey);
    }

    /**
     * @return list<string>
     */
    public static function activeAreasFor(string $themeId): array
    {
        $data = [
            'sub_theme' => VoodbuilderSettings::get('sub_theme'),
            'content_channel_sub_themes' => VoodbuilderSettings::get('content_channel_sub_themes', []),
        ];

        $areas = [];

        foreach (ActiveThemeMap::assignments($data) as $row) {
            if ($row['theme_id'] === $themeId) {
                $areas[] = $row['area_label'];
            }
        }

        return $areas;
    }

    public static function cardSurface(string $themeId): string
    {
        $accent = self::previewColor($themeId);

        return 'color-mix(in srgb, '.$accent.' 14%, #ffffff)';
    }

    public static function previewColor(string $themeId): string
    {
        $colors = ThemePalette::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $primary = ThemePalette::sanitizeColor($colors[$themeId]['light']['primary'] ?? null);

        if ($primary !== null) {
            return $primary;
        }

        $hash = crc32($themeId);

        return sprintf('#%06X', $hash & 0xFFFFFF);
    }

    /**
     * @return array<string, string|int|null>
     */
    public static function modeColors(string $themeId, string $mode): array
    {
        $colors = ThemePalette::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $theme = is_array($colors[$themeId] ?? null) ? $colors[$themeId] : [];
        $modeColors = is_array($theme[$mode] ?? null) ? $theme[$mode] : [];
        $resolved = [];

        foreach (self::COLOR_KEYS as $key) {
            $resolved[$key] = ThemePalette::sanitizeColor($modeColors[$key] ?? null);
        }

        $opacity = ThemePalette::sanitizeOpacity($modeColors['header_bg_opacity'] ?? null);

        if ($opacity !== null) {
            $resolved['header_bg_opacity'] = $opacity;
        }

        return $resolved;
    }

    /**
     * @param  array<string, ?string>  $colors
     * @return list<string>
     */
    public static function stripHexes(array $colors): array
    {
        $hexes = [];

        foreach (self::COLOR_KEYS as $key) {
            $value = $colors[$key] ?? null;

            if ($value !== null) {
                $hexes[] = $value;
            }
        }

        if ($hexes === []) {
            $hexes[] = '#94a3b8';
        }

        return $hexes;
    }

    public static function contrastColor(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return '#0f172a';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.62 ? '#0f172a' : '#f8fafc';
    }
}
