<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Support\Editor\VoodbuilderSectionEditorBlocks;

final class VoodbuilderPaths
{
    public static function packagePath(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function themeCssAbsolutePath(): string
    {
        return self::packagePath().'/resources/css/theme.css';
    }

    public static function themeCssRelativePath(): string
    {
        return self::relativeToBasePath(self::themeCssAbsolutePath());
    }

    /**
     * @return list<string>
     */
    public static function defaultViteEntries(): array
    {
        return [
            self::themeCssRelativePath(),
            'resources/js/app.js',
            self::relativeToBasePath(self::packagePath().'/resources/js/site-runtime.js'),
        ];
    }

    public static function editorViteEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/js/editor/editor/init.js');
    }

    public static function editorCssEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/css/editor/editor.css');
    }

    public static function editorBlockPreviewCssEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/css/editor/block-preview-shim.css');
    }

    public static function editorTabsCssEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/css/editor/tabs.css');
    }

    public static function editorFormsCssEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/css/editor/forms.css');
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

    public static function relativeToBasePath(string $absolutePath): string
    {
        $base = realpath(base_path()) ?: base_path();
        $target = realpath($absolutePath) ?: $absolutePath;

        $base = rtrim(str_replace('\\', '/', $base), '/');
        $target = str_replace('\\', '/', $target);

        if (str_starts_with($target, $base.'/')) {
            return substr($target, strlen($base) + 1);
        }

        $package = realpath(self::packagePath()) ?: self::packagePath();
        $package = rtrim(str_replace('\\', '/', $package), '/');

        if (str_starts_with($target, $package.'/')) {
            return substr($target, strlen($package) + 1);
        }

        return $target;
    }
}
