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
                return 'bg-vp-brand-2';
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
        return self::replaceFixedBackgroundColors($style);
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

    private static function isFixedDarkTextColor(string $value): bool
    {
        return preg_match('/^#(?:111|1f2937|374151|3c3c43|111827|000(?:000)?)$/', $value) === 1
            || preg_match('/^rgb\(\s*(?:17|31|55|60)\s*,/', $value) === 1;
    }
}
