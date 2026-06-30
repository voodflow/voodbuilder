<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderSectionGrapesJsBlocks;

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
        ];
    }

    public static function grapesJsViteEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/js/grapesjs/editor.js');
    }

    public static function grapesJsEditorCssEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/css/grapesjs/editor.css');
    }

    public static function grapesJsTabsCssEntry(): string
    {
        return self::relativeToBasePath(self::packagePath().'/resources/css/grapesjs/tabs.css');
    }

    /**
     * @return list<string>
     */
    public static function viteInputEntries(): array
    {
        $entries = [
            self::themeCssRelativePath(),
            self::grapesJsViteEntry(),
            self::grapesJsEditorCssEntry(),
        ];

        if (VoodbuilderSectionGrapesJsBlocks::isAvailable()) {
            $entries[] = VoodbuilderSectionGrapesJsBlocks::utilitiesCssEntry();
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    public static function grapesJsCanvasStyleEntries(): array
    {
        $entries = [
            self::themeCssRelativePath(),
            self::grapesJsTabsCssEntry(),
        ];

        if (VoodbuilderSectionGrapesJsBlocks::isAvailable()) {
            $entries[] = VoodbuilderSectionGrapesJsBlocks::utilitiesCssEntry();
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

        return $target;
    }
}
