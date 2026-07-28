<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\File;

final class FilamentMenuTreeAssets
{
    public const STYLE_ID = 'filament-menu-tree';

    public const SCRIPT_ID = 'menu-tree-view';

    public const PACKAGE = 'voodbuilder';

    public static function cssSourcePath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/css/filament-menu-tree.css';
    }

    public static function jsSourcePath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/js/filament-menu-tree-view.js';
    }

    public static function register(): void
    {
        $assets = [];

        if (is_file(self::cssSourcePath())) {
            self::ensurePublishedCss();
            $assets[] = Css::make(self::STYLE_ID, self::cssSourcePath());
        }

        if (is_file(self::jsSourcePath())) {
            $assets[] = AlpineComponent::make(self::SCRIPT_ID, self::jsSourcePath());
        }

        if ($assets === []) {
            return;
        }

        FilamentAsset::register($assets, self::PACKAGE);
    }

    public static function ensurePublishedCss(): void
    {
        if (! is_file(self::cssSourcePath())) {
            return;
        }

        $destination = public_path('css/'.self::PACKAGE.'/'.self::STYLE_ID.'.css');

        File::ensureDirectoryExists(dirname($destination));

        if (! is_file($destination) || filemtime(self::cssSourcePath()) > filemtime($destination)) {
            File::copy(self::cssSourcePath(), $destination);
        }
    }

    public static function renderStyleTag(): string
    {
        if (! is_file(self::cssSourcePath())) {
            return '';
        }

        self::ensurePublishedCss();

        return '<link rel="stylesheet" href="'.e(
            FilamentAsset::getStyleHref(self::STYLE_ID, self::PACKAGE),
        ).'" />';
    }
}
