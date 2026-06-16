<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

/**
 * Single convention for vpress visual themes (package-bundled and app-scaffolded).
 *
 * Package (shipped with vpress):
 *   - CSS:    packages/voodflow/vpress/resources/themes/{id}/theme.css
 *   - Views:  packages/voodflow/vpress/resources/views/themes/{id}/
 *   - Config: vpress::themes.{id}.layouts.*
 *
 * Application (php artisan vpress:make-subtheme):
 *   - CSS:    resources/vpress/themes/{id}/theme.css
 *   - Views:  resources/views/vpress/themes/{id}/
 *   - Config: vpress.themes.{id}.layouts.*
 *
 * Admin color overrides use html[data-vpress-sub-theme="{id}"] via ThemePalette.
 */
final class ThemeConvention
{
    public static function packageCssPath(string $id): string
    {
        return VpressPaths::packagePath()."/resources/themes/{$id}/theme.css";
    }

    public static function packageCssImport(): string
    {
        return '../themes/{id}/theme.css';
    }

    public static function appCssPath(string $id): string
    {
        return resource_path("vpress/themes/{$id}/theme.css");
    }

    public static function appCssRelativePath(string $id): string
    {
        return "resources/vpress/themes/{$id}/theme.css";
    }

    public static function appViewsPath(string $id): string
    {
        return resource_path("views/vpress/themes/{$id}");
    }

    public static function packageLayoutView(string $id, string $layout): string
    {
        return "vpress::themes.{$id}.layouts.{$layout}";
    }

    public static function appLayoutView(string $id, string $layout): string
    {
        return "vpress.themes.{$id}.layouts.{$layout}";
    }

    public static function cssImportPathFromBundle(string $absoluteCssPath): ?string
    {
        $bundleDir = dirname(VpressPaths::themeCssAbsolutePath());
        $absoluteCssPath = realpath($absoluteCssPath) ?: $absoluteCssPath;

        if (! is_file($absoluteCssPath)) {
            return null;
        }

        $relative = VpressPaths::relativeToBasePath($absoluteCssPath);

        if (str_starts_with($relative, 'packages/voodflow/vpress/resources/themes/')) {
            return '../themes/'.basename(dirname($absoluteCssPath)).'/theme.css';
        }

        if (str_starts_with($relative, 'vendor/voodflow/vpress/resources/themes/')) {
            return '../themes/'.basename(dirname($absoluteCssPath)).'/theme.css';
        }

        $fromBundle = str_replace('\\', '/', realpath($bundleDir) ?: $bundleDir);
        $toTheme = str_replace('\\', '/', $absoluteCssPath);

        $relativePath = self::relativePath($fromBundle, dirname($toTheme)).'/theme.css';

        return $relativePath !== '/theme.css' ? $relativePath : null;
    }

    private static function relativePath(string $from, string $to): string
    {
        $from = rtrim(str_replace('\\', '/', $from), '/');
        $to = rtrim(str_replace('\\', '/', $to), '/');

        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        return str_repeat('../', count($fromParts)).implode('/', $toParts);
    }
}
