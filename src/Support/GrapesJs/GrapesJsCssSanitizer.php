<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * GrapesJS can persist class-based background rules that match every section layout
 * (shared voodbuilder-gjs-section / body-font utilities). Those rules bleed
 * one uploaded image across the whole page.
 */
final class GrapesJsCssSanitizer
{
    public static function sanitize(string $css): string
    {
        if ($css === '' || ! str_contains($css, 'background')) {
            return $css;
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

    private static function declarationsIncludeBackgroundImage(string $declarations): bool
    {
        return preg_match('/\bbackground(?:-image)?\s*:[^;]*url\s*\(/i', $declarations) === 1;
    }

    private static function isBroadSectionLayoutSelector(string $selectors): bool
    {
        if (! str_contains($selectors, 'voodbuilder-gjs-section') && ! str_contains($selectors, 'body-font')) {
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
