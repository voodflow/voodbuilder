<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Models\VpressSettings;

final class ThemePalette
{
    /**
     * @return array<string, array{custom: bool, light: array{primary: ?string, secondary: ?string}, dark: array{primary: ?string, secondary: ?string}}>
     */
    public static function normalize(array $colors): array
    {
        $normalized = [];

        foreach (app(SubThemeRegistry::class)->ids() as $id) {
            $existing = is_array($colors[$id] ?? null) ? $colors[$id] : [];
            $light = is_array($existing['light'] ?? null) ? $existing['light'] : [];
            $dark = is_array($existing['dark'] ?? null) ? $existing['dark'] : [];

            $normalized[$id] = [
                'custom' => (bool) ($existing['custom'] ?? false),
                'light' => [
                    'primary' => self::sanitizeColor($light['primary'] ?? null),
                    'secondary' => self::sanitizeColor($light['secondary'] ?? null),
                ],
                'dark' => [
                    'primary' => self::sanitizeColor($dark['primary'] ?? null),
                    'secondary' => self::sanitizeColor($dark['secondary'] ?? null),
                ],
            ];
        }

        return $normalized;
    }

    public static function css(): string
    {
        $colors = ThemePalette::normalize(VpressSettings::get('sub_theme_colors', []));
        $rules = [];

        foreach ($colors as $subThemeId => $palette) {
            if (! ($palette['custom'] ?? false)) {
                continue;
            }

            $lightRule = self::buildRule((string) $subThemeId, $palette['light'], false);

            if ($lightRule !== null) {
                $rules[] = $lightRule;
            }

            $darkRule = self::buildRule((string) $subThemeId, $palette['dark'], true);

            if ($darkRule !== null) {
                $rules[] = $darkRule;
            }
        }

        if ($rules === []) {
            return '';
        }

        return implode("\n", $rules);
    }

    /**
     * @param  array{primary: ?string, secondary: ?string}  $palette
     */
    private static function buildRule(string $subThemeId, array $palette, bool $dark): ?string
    {
        $primary = $palette['primary'] ?? null;
        $secondary = $palette['secondary'] ?? null;

        if ($primary === null && $secondary === null) {
            return null;
        }

        if ($primary === null) {
            $primary = $secondary;
        }

        if ($secondary === null) {
            $secondary = $primary;
        }

        $selector = $dark
            ? "html.dark[data-vpress-sub-theme='{$subThemeId}']"
            : "html[data-vpress-sub-theme='{$subThemeId}']:not(.dark)";

        $brand2 = "color-mix(in srgb, {$primary} 50%, {$secondary} 50%)";

        return "{$selector}{--color-vp-brand-1:{$primary};--color-vp-brand-2:{$brand2};--color-vp-brand-3:{$secondary};}";
    }

    public static function sanitizeColor(mixed $color): ?string
    {
        if (! is_string($color) || $color === '') {
            return null;
        }

        $color = strtolower(trim($color));

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $color) !== 1) {
            return null;
        }

        if (strlen($color) === 4) {
            $color = '#'.implode('', array_map(
                static fn (string $char): string => $char.$char,
                str_split(substr($color, 1)),
            ));
        }

        return $color;
    }
}
