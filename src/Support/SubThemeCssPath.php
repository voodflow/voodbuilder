<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Resolve registered sub-theme CSS paths from package, app, or absolute plugin paths.
 */
final class SubThemeCssPath
{
    public static function absolute(?string $cssPath): ?string
    {
        if (! is_string($cssPath) || $cssPath === '') {
            return null;
        }

        if (self::isAbsoluteFilesystemPath($cssPath)) {
            return is_file($cssPath) ? $cssPath : null;
        }

        if (str_starts_with($cssPath, 'themes/')) {
            $absolute = VoodbuilderPaths::packagePath().'/resources/'.$cssPath;

            return is_file($absolute) ? $absolute : null;
        }

        if (str_starts_with($cssPath, 'resources/')) {
            $absolute = base_path($cssPath);

            return is_file($absolute) ? $absolute : null;
        }

        $absolute = base_path($cssPath);

        return is_file($absolute) ? $absolute : null;
    }

    public static function forTheme(string $subThemeId): ?string
    {
        return self::absolute(app(SubThemeRegistry::class)->cssPath($subThemeId));
    }

    private static function isAbsoluteFilesystemPath(string $path): bool
    {
        if (str_starts_with($path, '/')) {
            return true;
        }

        return preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
