<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

/**
 * Maps Tailblocks hardcoded Tailwind colors to Vpress theme tokens so blocks
 * adapt automatically to light/dark mode and backend palette overrides.
 */
final class TailblocksThemeTokenMigrator
{
    /**
     * @var list<string>
     */
    private const VARIANT_PREFIXES = [
        'hover:',
        'focus:',
        'active:',
        'group-hover:',
        'focus-within:',
    ];

    /**
     * @var list<string>
     */
    private const BRAND_COLORS = [
        'indigo',
        'yellow',
        'red',
        'purple',
        'pink',
        'blue',
        'green',
    ];

    /**
     * Default Tailwind palette hex values (not theme-specific overrides).
     * Used to replace hardcoded block colors with CSS variables while preserving
     * deliberate custom picks from the style manager.
     *
     * @var list<string>
     */
    private const BRAND_LIGHT_SURFACE_HEX = [
        '#eef2ff', '#e0e7ff', '#c7d2fe', '#eff6ff', '#dbeafe', '#bfdbfe',
        '#fefce8', '#fef9c3', '#fef2f2', '#fee2e2', '#faf5ff', '#f3e8ff',
        '#fdf2f8', '#fce7f3', '#f0fdf4', '#dcfce7',
    ];

    /**
     * @var list<string>
     */
    private const BRAND_MID_SURFACE_HEX = [
        '#a5b4fc', '#818cf8', '#6366f1', '#93c5fd', '#60a5fa', '#3b82f6',
        '#eab308', '#facc15', '#ef4444', '#f87171', '#a855f7', '#c084fc',
        '#ec4899', '#f472b6', '#22c55e', '#4ade80',
    ];

    /**
     * @var list<string>
     */
    private const BRAND_DARK_SURFACE_HEX = [
        '#4f46e5', '#4338ca', '#3730a3', '#312e81', '#2563eb', '#1d4ed8',
        '#1e40af', '#ca8a04', '#a16207', '#dc2626', '#b91c1c', '#9333ea',
        '#7e22ce', '#db2777', '#be185d', '#16a34a', '#15803d',
    ];

    public static function migrateHtml(string $html): string
    {
        $html = self::migrateSectionElements($html);
        $html = self::migrateInlineStyles($html);

        $html = preg_replace_callback(
            '/\bclass=(["\'])(.*?)\1/',
            static function (array $matches): string {
                $quote = $matches[1];
                $classes = self::migrateClassList($matches[2]);

                return 'class='.$quote.$classes.$quote;
            },
            $html,
        ) ?? $html;

        return $html;
    }

    public static function migrateCss(string $css): string
    {
        if ($css === '') {
            return $css;
        }

        $css = self::replaceFixedBackgroundColors($css);
        $css = self::replaceFixedBrandBackgroundColors($css);

        return preg_replace_callback(
            '/\bcolor:\s*([^;}{]+)/',
            static function (array $matches): string {
                $value = strtolower(trim($matches[1]));

                if (self::isFixedDarkTextColor($value)) {
                    return 'color: var(--color-vp-text-1)';
                }

                return $matches[0];
            },
            $css,
        ) ?? $css;
    }

    /**
     * @param  array<string, mixed>  $project
     * @return array<string, mixed>
     */
    public static function migrateProject(array $project): array
    {
        if (isset($project['styles']) && is_array($project['styles'])) {
            $project['styles'] = array_map(
                static function (mixed $styleRule): mixed {
                    if (! is_array($styleRule)) {
                        return $styleRule;
                    }

                    if (isset($styleRule['style']) && is_array($styleRule['style'])) {
                        $styleRule['style'] = self::migrateStyleObject($styleRule['style']);
                    }

                    return $styleRule;
                },
                $project['styles'],
            );
        }

        return self::migrateProjectNode($project);
    }

    public static function migrateClassList(string $classList): string
    {
        $tokens = preg_split('/\s+/', trim($classList), -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false || $tokens === []) {
            return $classList;
        }

        $migrated = array_map(static fn (string $token): string => self::migrateToken($token), $tokens);

        return implode(' ', $migrated);
    }

    public static function migrateToken(string $token): string
    {
        foreach (self::VARIANT_PREFIXES as $prefix) {
            if (str_starts_with($token, $prefix)) {
                return $prefix.self::migrateToken(substr($token, strlen($prefix)));
            }
        }

        return match ($token) {
            'bg-white' => 'bg-vp-bg-elv',
            'bg-gray-50', 'bg-gray-100' => 'bg-vp-bg-alt',
            'bg-gray-200', 'bg-gray-300' => 'bg-vp-gray-soft',
            'text-gray-900', 'text-gray-800', 'text-gray-700' => 'text-vp-text-1',
            'text-gray-600', 'text-gray-500' => 'text-vp-text-2',
            'text-gray-400', 'text-gray-300' => 'text-vp-text-3',
            'border-gray-100', 'border-gray-200', 'border-gray-300' => 'border-vp-divider',
            'divide-gray-100', 'divide-gray-200', 'divide-gray-300' => 'divide-vp-divider',
            default => self::migrateBrandToken($token) ?? $token,
        };
    }

    private static function migrateBrandToken(string $token): ?string
    {
        if (preg_match('/^(bg|text|border|ring|from|to|via)-('.implode('|', self::BRAND_COLORS).')-(\d+)$/', $token, $matches) !== 1) {
            return null;
        }

        $utility = $matches[1];
        $shade = (int) $matches[3];

        if ($utility === 'bg') {
            if ($shade <= 100) {
                return 'bg-vp-gray-soft';
            }

            if ($shade >= 600) {
                return 'bg-vp-brand-3';
            }

            return 'bg-vp-brand-1';
        }

        if ($utility === 'text') {
            if ($shade >= 600) {
                return 'text-vp-brand-2';
            }

            return 'text-vp-brand-1';
        }

        if ($utility === 'border') {
            return 'border-vp-brand-1';
        }

        if ($utility === 'ring') {
            return 'ring-vp-brand-1/20';
        }

        if (in_array($utility, ['from', 'to', 'via'], true)) {
            return $utility.'-vp-brand-1';
        }

        return null;
    }

    private static function migrateSectionElements(string $html): string
    {
        return preg_replace_callback(
            '/<section\b([^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];

                if (preg_match('/\bclass=(["\'])(.*?)\1/', $attrs, $classMatch) === 1) {
                    $classes = self::ensureSectionClasses($classMatch[2]);
                    $attrs = preg_replace(
                        '/\bclass=(["\'])(.*?)\1/',
                        'class='.$classMatch[1].$classes.$classMatch[1],
                        $attrs,
                        1,
                    ) ?? $attrs;
                } else {
                    $attrs .= ' class="vpress-gjs-section bg-vp-bg"';
                }

                return '<section'.$attrs.'>';
            },
            $html,
        ) ?? $html;
    }

    private static function ensureSectionClasses(string $classes): string
    {
        $tokens = preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (! in_array('vpress-gjs-section', $tokens, true)) {
            $tokens[] = 'vpress-gjs-section';
        }

        if (! self::hasBackgroundClass($tokens)) {
            $tokens[] = 'bg-vp-bg';
        }

        return implode(' ', $tokens);
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function hasBackgroundClass(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (preg_match('/^bg-(?!opacity|blend|clip|origin|size|position|repeat|none|auto)/', $token) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function migrateInlineStyles(string $html): string
    {
        return preg_replace_callback(
            '/\bstyle=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                return 'style='.$matches[1].self::migrateStyleDeclaration($matches[2]).$matches[1];
            },
            $html,
        ) ?? $html;
    }

    private static function migrateStyleDeclaration(string $style): string
    {
        $style = self::replaceFixedBackgroundColors($style);

        return self::replaceFixedBrandBackgroundColors($style);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function migrateProjectNode(array $node): array
    {
        if (isset($node['component']) && is_string($node['component'])) {
            $node['component'] = self::migrateHtml($node['component']);
        }

        if (isset($node['styles']) && is_string($node['styles'])) {
            $node['styles'] = self::migrateCss($node['styles']);
        }

        if (isset($node['attributes']) && is_array($node['attributes'])) {
            $node['attributes'] = self::migrateComponentAttributes($node['attributes']);
        }

        if (isset($node['classes']) && is_array($node['classes'])) {
            $node['classes'] = self::migrateComponentClassesArray($node['classes']);
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (in_array($key, ['attributes', 'classes', 'style'], true)) {
                continue;
            }

            if (self::isListArray($value)) {
                $node[$key] = array_map(
                    static fn (mixed $item): mixed => is_array($item) ? self::migrateProjectNode($item) : $item,
                    $value,
                );
            } else {
                $node[$key] = self::migrateProjectNode($value);
            }
        }

        return $node;
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private static function migrateComponentAttributes(array $attrs): array
    {
        if (isset($attrs['class']) && is_string($attrs['class'])) {
            $attrs['class'] = self::migrateClassList($attrs['class']);
        }

        if (isset($attrs['style']) && is_string($attrs['style'])) {
            $attrs['style'] = self::migrateStyleDeclaration($attrs['style']);
        }

        return $attrs;
    }

    /**
     * @param  list<mixed>  $classes
     * @return list<mixed>
     */
    private static function migrateComponentClassesArray(array $classes): array
    {
        return array_map(
            static function (mixed $item): mixed {
                if (! is_array($item) || ! isset($item['name']) || ! is_string($item['name'])) {
                    return $item;
                }

                $item['name'] = self::migrateToken($item['name']);

                return $item;
            },
            $classes,
        );
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private static function migrateStyleObject(array $style): array
    {
        foreach ($style as $property => $value) {
            if (! is_string($value)) {
                continue;
            }

            $normalized = strtolower(trim($value));

            if (preg_match('/^background(?:-color)?$/i', (string) $property) === 1) {
                $replacement = self::brandBackgroundVariableForHex($normalized);

                if ($replacement !== null) {
                    $style[$property] = $replacement;
                }

                continue;
            }

            if ((string) $property === 'color' && self::isFixedDarkTextColor($normalized)) {
                $style[$property] = 'var(--color-vp-text-1)';
            }
        }

        return $style;
    }

    private static function replaceFixedBackgroundColors(string $value): string
    {
        $value = preg_replace(
            '/background(?:-color)?:\s*(?:#(?:fff(?:fff)?|fefefe|fafafa|f9fafb|f6f6f7|f4f5f7|f3f4f6|e5e7eb)|rgb\(\s*255\s*,\s*255\s*,\s*255\s*\)|white)\s*(;|$)/i',
            'background-color: var(--color-vp-bg-elv)$1',
            $value,
        ) ?? $value;

        return preg_replace(
            '/background(?:-color)?:\s*(?:#(?:f0f0f0|eeeeee|e5e7eb|edf2f7)|rgb\(\s*244\s*,\s*245\s*,\s*247\s*\))\s*(;|$)/i',
            'background-color: var(--color-vp-bg-alt)$1',
            $value,
        ) ?? $value;
    }

    private static function replaceFixedBrandBackgroundColors(string $value): string
    {
        return preg_replace_callback(
            '/background(?:-color)?:\s*(#[0-9a-f]{3,8})\b/i',
            static function (array $matches): string {
                $replacement = self::brandBackgroundVariableForHex(strtolower($matches[1]));

                if ($replacement === null) {
                    return $matches[0];
                }

                return 'background-color: '.$replacement;
            },
            $value,
        ) ?? $value;
    }

    private static function brandBackgroundVariableForHex(string $hex): ?string
    {
        $expanded = self::expandHex($hex);

        if ($expanded === null) {
            return null;
        }

        if (in_array($expanded, self::BRAND_LIGHT_SURFACE_HEX, true)) {
            return 'var(--color-vp-gray-soft)';
        }

        if (in_array($expanded, self::BRAND_DARK_SURFACE_HEX, true)) {
            return 'var(--color-vp-brand-3)';
        }

        if (in_array($expanded, self::BRAND_MID_SURFACE_HEX, true)) {
            return 'var(--color-vp-brand-1)';
        }

        return null;
    }

    private static function expandHex(string $hex): ?string
    {
        if (preg_match('/^#([0-9a-f]{3})$/', $hex, $matches) === 1) {
            $chars = str_split($matches[1]);

            return '#'.$chars[0].$chars[0].$chars[1].$chars[1].$chars[2].$chars[2];
        }

        if (preg_match('/^#([0-9a-f]{6})$/', $hex) === 1) {
            return $hex;
        }

        return null;
    }

    /**
     * @param  array<mixed>  $array
     */
    private static function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    private static function isFixedDarkTextColor(string $value): bool
    {
        return preg_match('/^#(?:111|1f2937|374151|3c3c43|111827|000(?:000)?)$/', $value) === 1
            || preg_match('/^rgb\(\s*(?:17|31|55|60)\s*,/', $value) === 1;
    }
}
