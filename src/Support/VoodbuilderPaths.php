<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Support\Editor\VoodbuilderSectionEditorBlocks;

/**
 * Voodbuilder Paths.
 */
final class VoodbuilderPaths
{
    public static function packagePath(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Root used for Vite input paths / manifest lookups.
     *
     * Prefer a path-repo checkout under packages/voodflow/voodbuilder when present,
     * so mirrored vendor installs (Composer symlink: false) still match vite.config.js
     * and public/build/manifest.json keys.
     */
    public static function viteSourcePath(): string
    {
        $packages = base_path('packages/voodflow/voodbuilder');

        if (is_dir($packages.DIRECTORY_SEPARATOR.'resources')) {
            return $packages;
        }

        return self::packagePath();
    }

    public static function themeCssAbsolutePath(): string
    {
        return self::packagePath().'/resources/css/theme.css';
    }

    public static function themeCssRelativePath(): string
    {
        return self::relativeToBasePath(self::viteSourcePath().'/resources/css/theme.css');
    }

    /**
     * @return list<string>
     */
    public static function defaultViteEntries(): array
    {
        return [
            self::themeCssRelativePath(),
            'resources/js/app.js',
            self::relativeToBasePath(self::viteSourcePath().'/resources/js/site-runtime.js'),
        ];
    }

    public static function editorViteEntry(): string
    {
        return self::relativeToBasePath(self::viteSourcePath().'/resources/js/editor/editor/init.js');
    }

    public static function editorCssEntry(): string
    {
        return self::relativeToBasePath(self::viteSourcePath().'/resources/css/editor/editor.css');
    }

    public static function editorBlockPreviewCssEntry(): string
    {
        return self::relativeToBasePath(self::viteSourcePath().'/resources/css/editor/block-preview-shim.css');
    }

    public static function editorTabsCssEntry(): string
    {
        return self::relativeToBasePath(self::viteSourcePath().'/resources/css/editor/tabs.css');
    }

    public static function editorFormsCssEntry(): string
    {
        return self::relativeToBasePath(self::viteSourcePath().'/resources/css/editor/forms.css');
    }

    /**
     * @return list<string>
     */
    public static function viteInputEntries(): array
    {
        $entries = [
            self::themeCssRelativePath(),
            self::editorViteEntry(),
            self::editorCssEntry(),
            self::editorTabsCssEntry(),
            self::editorFormsCssEntry(),
        ];

        if (VoodbuilderSectionEditorBlocks::isAvailable()) {
            $entries[] = VoodbuilderSectionEditorBlocks::utilitiesCssEntry();
            $entries[] = self::editorBlockPreviewCssEntry();
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    public static function editorCanvasStyleEntries(): array
    {
        $entries = [
            self::themeCssRelativePath(),
            self::editorTabsCssEntry(),
            self::editorFormsCssEntry(),
        ];

        if (VoodbuilderSectionEditorBlocks::isAvailable()) {
            $entries[] = VoodbuilderSectionEditorBlocks::utilitiesCssEntry();
        }

        return $entries;
    }

    public static function isVendorInstall(): bool
    {
        return str_contains(str_replace('\\', '/', self::packagePath()), '/vendor/voodflow/voodbuilder');
    }

    /**
     * Manifest keys to try for a Vite entry (vendor mirror ↔ packages path-repo).
     *
     * @return list<string>
     */
    public static function viteManifestKeys(string $entry): array
    {
        $entry = ltrim(str_replace('\\', '/', $entry), '/');
        $keys = [$entry];
        $vendorPrefix = 'vendor/voodflow/voodbuilder/';
        $packagesPrefix = 'packages/voodflow/voodbuilder/';

        if (str_starts_with($entry, $vendorPrefix)) {
            $keys[] = $packagesPrefix.substr($entry, strlen($vendorPrefix));
        } elseif (str_starts_with($entry, $packagesPrefix)) {
            $keys[] = $vendorPrefix.substr($entry, strlen($packagesPrefix));
        }

        return array_values(array_unique($keys));
    }

    public static function relativeToBasePath(string $absolutePath): string
    {
        $base = realpath(base_path()) ?: base_path();
        $target = realpath($absolutePath) ?: $absolutePath;

        $base = rtrim(str_replace('\\', '/', $base), '/');
        $target = str_replace('\\', '/', $target);

        if (str_starts_with($target, $base.'/')) {
            return substr($target, strlen($base) + 1);
        }

        $package = realpath(self::viteSourcePath()) ?: self::viteSourcePath();
        $package = rtrim(str_replace('\\', '/', $package), '/');

        if (str_starts_with($target, $package.'/')) {
            return substr($target, strlen($package) + 1);
        }

        return $target;
    }
}
