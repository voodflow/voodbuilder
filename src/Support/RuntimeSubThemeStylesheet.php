<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Serve app-created sub-theme CSS at request time (no Vite entry / npm build).
 *
 * Theme Studio clones and `voodbuilder:make-subtheme` write plain CSS under
 * `resources/voodbuilder/themes/{id}/`. Tailwind-only directives left over from
 * older stubs are stripped so a broken @apply cannot take down the site.
 */
final class RuntimeSubThemeStylesheet
{
    public static function forTheme(string $subThemeId): string
    {
        $absolute = SubThemeCssPath::forTheme($subThemeId);

        if ($absolute === null || ! is_readable($absolute) || ! self::isRuntimeServedPath($absolute)) {
            return '';
        }

        return self::sanitize((string) file_get_contents($absolute));
    }

    public static function isRuntimeServedPath(string $absoluteCssPath): bool
    {
        $relative = VoodbuilderPaths::relativeToBasePath($absoluteCssPath);

        return str_starts_with($relative, 'resources/voodbuilder/themes/');
    }

    public static function isRuntimeServedTheme(string $subThemeId): bool
    {
        $absolute = SubThemeCssPath::forTheme($subThemeId);

        return $absolute !== null && self::isRuntimeServedPath($absolute);
    }

    public static function sanitize(string $css): string
    {
        $cleaned = preg_replace(
            '/^\s*@(?:reference|import|theme|source|plugin|custom-variant)\b[^;]*;/mi',
            '',
            $css,
        ) ?? $css;

        $cleaned = preg_replace('/^\s*@apply\b[^;]*;/mi', '', $cleaned) ?? $cleaned;

        // Drop rules that became empty after stripping @apply.
        $cleaned = preg_replace('/[^{};\n]+\{(?:\s*)\}/m', '', $cleaned) ?? $cleaned;

        $cleaned = preg_replace("/\n{3,}/", "\n\n", $cleaned) ?? $cleaned;

        return trim($cleaned);
    }
}
