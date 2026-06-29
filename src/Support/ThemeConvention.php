<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Single convention for voodbuilder visual themes (package-bundled and app-scaffolded).
 *
 * Package (shipped with voodbuilder):
 *   - CSS:    packages/voodflow/voodbuilder/resources/themes/{id}/theme.css
 *   - Views:  packages/voodflow/voodbuilder/resources/views/themes/{id}/
 *   - Config: voodbuilder::themes.{id}.layouts.*
 *
 * Application (php artisan voodbuilder:make-subtheme):
 *   - CSS:    resources/voodbuilder/themes/{id}/theme.css
 *   - Views:  resources/views/voodbuilder/themes/{id}/
 *   - Config: voodbuilder.themes.{id}.layouts.*
 *
 * Admin color overrides use html[data-voodbuilder-sub-theme="{id}"] via ThemePalette.
 */
final class ThemeConvention
{
    public static function packageCssPath(string $id): string
    {
        return VoodbuilderPaths::packagePath()."/resources/themes/{$id}/theme.css";
    }

    public static function packageCssImport(): string
    {
        return '../themes/{id}/theme.css';
    }

    public static function appCssPath(string $id): string
    {
        return resource_path("voodbuilder/themes/{$id}/theme.css");
    }

    public static function appCssRelativePath(string $id): string
    {
        return "resources/voodbuilder/themes/{$id}/theme.css";
    }

    public static function appViewsPath(string $id): string
    {
        return resource_path("views/voodbuilder/themes/{$id}");
    }

    public static function packageLayoutView(string $id, string $layout): string
    {
        return "voodbuilder::themes.{$id}.layouts.{$layout}";
    }

    public static function appLayoutView(string $id, string $layout): string
    {
        return "voodbuilder.themes.{$id}.layouts.{$layout}";
    }

    public static function cssImportPathFromBundle(string $absoluteCssPath): ?string
    {
        $bundleDir = dirname(VoodbuilderPaths::themeCssAbsolutePath());
        $absoluteCssPath = realpath($absoluteCssPath) ?: $absoluteCssPath;

        if (! is_file($absoluteCssPath)) {
            return null;
        }

        $relative = VoodbuilderPaths::relativeToBasePath($absoluteCssPath);

        if (str_starts_with($relative, 'packages/voodflow/voodbuilder/resources/themes/')) {
            return '../themes/'.basename(dirname($absoluteCssPath)).'/theme.css';
        }

        if (str_starts_with($relative, 'vendor/voodflow/voodbuilder/resources/themes/')) {
            return '../themes/'.basename(dirname($absoluteCssPath)).'/theme.css';
        }

        $fromBundle = str_replace('\\', '/', realpath($bundleDir) ?: $bundleDir);
        $toTheme = str_replace('\\', '/', $absoluteCssPath);

        $relativePath = self::relativePath($fromBundle, dirname($toTheme)).'/theme.css';

        return $relativePath !== '/theme.css' ? $relativePath : null;
    }

    public static function rewriteThemeId(string $contents, string $fromId, string $toId): string
    {
        if ($fromId === $toId || $fromId === '') {
            return $contents;
        }

        $pairs = [
            "data-voodbuilder-sub-theme='{$fromId}'" => "data-voodbuilder-sub-theme='{$toId}'",
            'data-voodbuilder-sub-theme="'.$fromId.'"' => 'data-voodbuilder-sub-theme="'.$toId.'"',
            "voodbuilder-sub-theme-{$fromId}" => "voodbuilder-sub-theme-{$toId}",
        ];

        return str_replace(array_keys($pairs), array_values($pairs), $contents);
    }

    /**
     * @param  list<string>  $extensions
     */
    public static function rewriteThemeIdInDirectory(string $directory, string $fromId, string $toId, array $extensions = ['css', 'blade.php']): void
    {
        if ($fromId === $toId || ! is_dir($directory)) {
            return;
        }

        foreach (File::allFiles($directory) as $file) {
            $extension = $file->getExtension();

            if (! in_array($extension, $extensions, true)) {
                continue;
            }

            $path = $file->getPathname();
            $contents = File::get($path);
            $rewritten = self::rewriteThemeId($contents, $fromId, $toId);

            if ($rewritten !== $contents) {
                File::put($path, $rewritten);
            }
        }
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
