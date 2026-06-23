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
        $html = self::stripConflictingInlineTextColors($html);
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
        $css = self::stripLegacyButtonCss($css);

        $css = str_replace(
            [
                'background-color: #6366f1',
                'background-color: #4f46e5',
            ],
            [
                'background-color: var(--color-vp-brand-1)',
                'background-color: var(--color-vp-brand-2)',
            ],
            $css,
        );

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
            $project['styles'] = array_values(array_filter(
                array_map(
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
                ),
                static function (mixed $styleRule): bool {
                    if (! is_array($styleRule)) {
                        return true;
                    }

                    return ! self::styleRuleTargetsLegacyButton($styleRule);
                },
            ));
        }

        return self::migrateProjectNode($project);
    }

    public static function hasExplicitTextColorClass(string $classList): bool
    {
        $tokens = preg_split('/\s+/', trim($classList), -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false) {
            return false;
        }

        foreach ($tokens as $token) {
            if (preg_match('/^text-(xs|sm|base|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl|8xl|9xl|left|center|right|justify|start|end)$/', $token) === 1) {
                continue;
            }

            if (str_starts_with($token, 'text-')) {
                return true;
            }
        }

        return false;
    }

    private static function stripConflictingInlineTextColors(string $html): string
    {
        return preg_replace_callback(
            '/<([a-z][\w:.-]*)\b([^>]*?)>/i',
            static function (array $matches): string {
                $attrs = $matches[2];

                if (preg_match('/\bclass=(["\'])(.*?)\1/i', $attrs, $classMatch) !== 1) {
                    return $matches[0];
                }

                if (! self::hasExplicitTextColorClass($classMatch[2])) {
                    return $matches[0];
                }

                if (preg_match('/\bstyle=(["\'])(.*?)\1/i', $attrs, $styleMatch) !== 1) {
                    return $matches[0];
                }

                $newStyle = self::removeColorFromStyleDeclaration($styleMatch[2]);

                if ($newStyle === trim($styleMatch[2])) {
                    return $matches[0];
                }

                if ($newStyle === '') {
                    $attrs = preg_replace('/\s*\bstyle=(["\'])(.*?)\1/i', '', $attrs) ?? $attrs;
                } else {
                    $attrs = preg_replace(
                        '/\bstyle=(["\'])(.*?)\1/i',
                        'style='.$styleMatch[1].$newStyle.$styleMatch[1],
                        $attrs,
                        1,
                    ) ?? $attrs;
                }

                return '<'.$matches[1].$attrs.'>';
            },
            $html,
        ) ?? $html;
    }

    private static function removeColorFromStyleDeclaration(string $style): string
    {
        $style = preg_replace('/\bcolor\s*:\s*[^;]+;?/i', '', $style) ?? $style;

        return trim($style, " \t\n\r\0\x0B;");
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function classListFromNode(array $node): ?string
    {
        if (isset($node['attributes']['class']) && is_string($node['attributes']['class'])) {
            return $node['attributes']['class'];
        }

        if (isset($node['classes']) && is_array($node['classes']) && is_string($node['classes'][0] ?? null)) {
            return implode(' ', $node['classes']);
        }

        return null;
    }

    public static function migrateClassList(string $classList): string
    {
        $tokens = preg_split('/\s+/', trim($classList), -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false || $tokens === []) {
            return $classList;
        }

        $tokens = array_map(static fn (string $token): string => self::migrateToken($token), $tokens);
        $tokens = self::migrateLegacyButtonClassesArray($tokens);
        $tokens = self::normalizeBrandBackgroundClasses($tokens);

        return implode(' ', $tokens);
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    public static function normalizeBrandBackgroundClasses(array $tokens): array
    {
        foreach (array_merge([''], self::VARIANT_PREFIXES) as $prefix) {
            $tokens = self::dedupeBrandBackgroundSlot($tokens, $prefix);
        }

        return $tokens;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function dedupeBrandBackgroundSlot(array $tokens, string $prefix): array
    {
        $pattern = '/^'.preg_quote($prefix, '/').'bg-vp-brand-\d+/';

        $matches = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => preg_match($pattern, $token) === 1,
        ));

        if (count($matches) <= 1) {
            return $tokens;
        }

        $keep = self::preferredBrandBackgroundClass($prefix, $matches);

        return array_values(array_filter(
            $tokens,
            static function (string $token) use ($pattern, $keep): bool {
                if (preg_match($pattern, $token) !== 1) {
                    return true;
                }

                return $token === $keep;
            },
        ));
    }

    /**
     * @param  list<string>  $candidates
     */
    private static function preferredBrandBackgroundClass(string $prefix, array $candidates): string
    {
        $priorities = match ($prefix) {
            'hover:' => ['hover:bg-vp-brand-2', 'hover:bg-vp-brand-1', 'hover:bg-vp-brand-3'],
            'focus:' => ['focus:bg-vp-brand-2', 'focus:bg-vp-brand-1', 'focus:bg-vp-brand-3'],
            'active:' => ['active:bg-vp-brand-2', 'active:bg-vp-brand-1', 'active:bg-vp-brand-3'],
            'group-hover:' => ['group-hover:bg-vp-brand-2', 'group-hover:bg-vp-brand-1', 'group-hover:bg-vp-brand-3'],
            'focus-within:' => ['focus-within:bg-vp-brand-2', 'focus-within:bg-vp-brand-1', 'focus-within:bg-vp-brand-3'],
            default => ['bg-vp-brand-1', 'bg-vp-brand-2', 'bg-vp-brand-3'],
        };

        foreach ($priorities as $preferred) {
            if (in_array($preferred, $candidates, true)) {
                return $preferred;
            }
        }

        return $candidates[0];
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    public static function migrateLegacyButtonClassesArray(array $tokens): array
    {
        if (! in_array('vpress-gjs-btn-primary', $tokens, true)) {
            return $tokens;
        }

        $tokens = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => $token !== 'vpress-gjs-btn-primary',
        ));

        foreach (['bg-vp-brand-1', 'text-white', 'hover:bg-vp-brand-2'] as $className) {
            if (! in_array($className, $tokens, true)) {
                $tokens[] = $className;
            }
        }

        return $tokens;
    }

    /**
     * @param  list<string>  $tokens
     */
    public static function migrateLegacyButtonClasses(array $tokens): string
    {
        return implode(' ', self::migrateLegacyButtonClassesArray($tokens));
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
            'text-gray-400', 'text-gray-300', 'text-gray-200' => 'text-vp-text-3',
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

    private static function migrateStyleDeclaration(string $style, ?string $classList = null): string
    {
        if ($classList !== null && self::hasExplicitTextColorClass($classList)) {
            $style = self::removeColorFromStyleDeclaration($style);
        }

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
            $node['classes'] = self::migrateComponentClasses($node['classes']);
        }

        if (isset($node['style']) && is_array($node['style'])) {
            $node['style'] = self::migrateStyleObject($node['style'], self::classListFromNode($node));
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
        $classList = null;

        if (isset($attrs['class']) && is_string($attrs['class'])) {
            $classList = self::migrateClassList($attrs['class']);
            $attrs['class'] = $classList;
        }

        if (isset($attrs['style']) && is_string($attrs['style'])) {
            $attrs['style'] = self::migrateStyleDeclaration($attrs['style'], $classList);
        }

        return $attrs;
    }

    /**
     * @param  list<mixed>  $classes
     * @return list<mixed>
     */
    private static function migrateComponentClasses(array $classes): array
    {
        if ($classes === []) {
            return $classes;
        }

        if (is_string($classes[0] ?? null)) {
            $tokens = array_map(
                static fn (string $token): string => self::migrateToken($token),
                array_values(array_filter($classes, 'is_string')),
            );

            $tokens = self::migrateLegacyButtonClassesArray($tokens);
            $tokens = self::normalizeBrandBackgroundClasses($tokens);

            return $tokens;
        }

        return self::migrateComponentClassesArray($classes);
    }

    /**
     * @param  list<mixed>  $classes
     * @return list<mixed>
     */
    private static function migrateComponentClassesArray(array $classes): array
    {
        $names = array_map(
            static function (mixed $item): ?string {
                if (! is_array($item) || ! isset($item['name']) || ! is_string($item['name'])) {
                    return null;
                }

                return self::migrateToken($item['name']);
            },
            $classes,
        );

        $names = array_values(array_filter($names, 'is_string'));
        $names = self::migrateLegacyButtonClassesArray($names);
        $names = self::normalizeBrandBackgroundClasses($names);

        return $names;
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private static function migrateStyleObject(array $style, ?string $classList = null): array
    {
        foreach ($style as $property => $value) {
            if (! is_string($value)) {
                continue;
            }

            $normalized = strtolower(trim($value));

            if (preg_match('/^background(?:-color)?$/i', (string) $property) === 1) {
                $replacement = self::brandBackgroundVariableForColor($normalized);

                if ($replacement !== null) {
                    $style[$property] = $replacement;
                }

                continue;
            }

            if ((string) $property === 'color') {
                if ($classList !== null && self::hasExplicitTextColorClass($classList)) {
                    unset($style[$property]);

                    continue;
                }

                if (self::isFixedDarkTextColor($normalized)) {
                    $style[$property] = 'var(--color-vp-text-1)';

                    continue;
                }

                if (self::isLibraryLightTextColor($normalized)) {
                    unset($style[$property]);
                }
            }
        }

        return $style;
    }

    private static function isLibraryLightTextColor(string $value): bool
    {
        if (in_array($value, ['white', '#fff', '#ffffff'], true)) {
            return true;
        }

        $expanded = self::expandHex($value);

        if ($expanded === null) {
            return false;
        }

        return in_array($expanded, [
            '#f3f4f6', '#e5e7eb', '#d1d5db', '#9ca3af',
            '#a5b4fc', '#818cf8', '#6366f1', '#c7d2fe', '#93c5fd',
        ], true);
    }

    private static function styleRuleTargetsLegacyButton(array $styleRule): bool
    {
        $selectors = $styleRule['selectors'] ?? [];

        if (! is_array($selectors)) {
            return false;
        }

        foreach ($selectors as $selector) {
            if (is_string($selector) && str_contains($selector, 'vpress-gjs-btn-primary')) {
                return true;
            }
        }

        return false;
    }

    private static function brandBackgroundVariableForColor(string $value): ?string
    {
        $hex = self::normalizeColorToHex($value);

        if ($hex === null) {
            return null;
        }

        return self::brandBackgroundVariableForHex($hex);
    }

    private static function normalizeColorToHex(string $value): ?string
    {
        $value = strtolower(trim($value));

        $expanded = self::expandHex($value);

        if ($expanded !== null) {
            return $expanded;
        }

        if (preg_match('/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/', $value, $matches) === 1) {
            return sprintf(
                '#%02x%02x%02x',
                min(255, (int) $matches[1]),
                min(255, (int) $matches[2]),
                min(255, (int) $matches[3]),
            );
        }

        return null;
    }

    private static function stripLegacyButtonCss(string $css): string
    {
        return preg_replace(
            '/\.vpress-gjs-btn-primary(?::hover)?\s*\{[^}]*\}\s*/',
            '',
            $css,
        ) ?? $css;
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
