<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Editor can persist class-based background rules that match every section layout
 * (shared voodbuilder-editor-section / body-font utilities). Those rules bleed
 * one uploaded image across the whole page.
 *
 * Also lightens published sheets: camelCase CSS props (invalid in browsers) and
 * redundant Tailwind `@layer properties` / duplicate `@property` resets that
 * balloon page weight and Save recompile time.
 */
final class EditorCssSanitizer
{
    public static function sanitize(string $css): string
    {
        if ($css === '') {
            return $css;
        }

        $css = self::stripRedundantTailwindPropertyLayers($css);
        $css = self::kebabCaseCamelCssProperties($css);

        if (! str_contains($css, 'background')) {
            return self::collapseDuplicateResets($css);
        }

        $sanitized = preg_replace_callback(
            '/([^{}]+)\{([^{}]*)\}/',
            static function (array $matches): string {
                $selectors = trim($matches[1]);
                $declarations = $matches[2];

                if (! self::declarationsIncludeBackgroundImage($declarations)) {
                    return $matches[0];
                }

                if (! self::isBroadSectionLayoutSelector($selectors)) {
                    return $matches[0];
                }

                return '';
            },
            $css,
        ) ?? $css;

        return self::collapseDuplicateResets($sanitized);
    }

    /**
     * Drop Tailwind v4 `@layer properties` reset blocks and orphan `@property --tw-*`
     * definitions that are already shipped by theme.css (pasted-component compiles
     * embed a full copy per component).
     */
    public static function stripRedundantTailwindPropertyLayers(string $css): string
    {
        if ($css === '') {
            return $css;
        }

        if (str_contains($css, '@layer properties')) {
            $css = self::stripBalancedAtRule($css, 'layer properties');
            $css = preg_replace('/@layer\s+properties\s*;/', '', $css) ?? $css;
        }

        if (str_contains($css, '@property --tw-')) {
            $css = preg_replace(
                '/@property\s+--tw-[a-z0-9-]+\s*\{[^{}]*\}/i',
                '',
                $css,
            ) ?? $css;
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $css) ?? $css);
    }

    /**
     * Convert React-style camelCase CSS properties to valid kebab-case.
     */
    public static function kebabCaseCamelCssProperties(string $css): string
    {
        if ($css === '' || ! preg_match('/[a-z][A-Z]/', $css)) {
            return $css;
        }

        return preg_replace_callback(
            '/([a-z]\w*[A-Z]\w*)\s*:/',
            static function (array $match): string {
                $property = $match[1];

                // Leave custom properties and already-kebab alone.
                if (str_starts_with($property, '--') || str_contains($property, '-')) {
                    return $match[0];
                }

                $kebab = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $property) ?? $property);

                return $kebab.':';
            },
            $css,
        ) ?? $css;
    }

    private static function stripBalancedAtRule(string $css, string $atName): string
    {
        $needle = '@'.$atName;
        $offset = 0;
        $length = strlen($css);

        while (($start = stripos($css, $needle, $offset)) !== false) {
            $brace = strpos($css, '{', $start);

            if ($brace === false) {
                break;
            }

            $depth = 0;
            $i = $brace;
            $end = null;

            for (; $i < $length; $i++) {
                $char = $css[$i];

                if ($char === '{') {
                    $depth++;
                } elseif ($char === '}') {
                    $depth--;

                    if ($depth === 0) {
                        $end = $i;
                        break;
                    }
                }
            }

            if ($end === null) {
                break;
            }

            $css = substr($css, 0, $start).substr($css, $end + 1);
            $length = strlen($css);
            $offset = $start;
        }

        return $css;
    }

    private static function declarationsIncludeBackgroundImage(string $declarations): bool
    {
        return preg_match('/\bbackground(?:-image)?\s*:[^;]*url\s*\(/i', $declarations) === 1;
    }

    private static function isBroadSectionLayoutSelector(string $selectors): bool
    {
        if (! str_contains($selectors, 'voodbuilder-editor-section') && ! str_contains($selectors, 'body-font')) {
            return false;
        }

        foreach (explode(',', $selectors) as $selector) {
            $selector = trim($selector);

            if ($selector === '' || str_contains($selector, '#')) {
                return false;
            }
        }

        return true;
    }

    private static function collapseDuplicateResets(string $css): string
    {
        $reset = '* { box-sizing: border-box; } body {margin: 0;}';

        while (str_contains($css, $reset.$reset)) {
            $css = str_replace($reset.$reset, $reset, $css);
        }

        return $css;
    }
}
