<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\File;

/**
 * Filament Admin Assets.
 */
final class FilamentAdminAssets
{
    public const STYLE_ID = 'filament-database-notifications';

    public const PACKAGE = 'voodbuilder';

    public static function sourcePath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/css/filament-database-notifications.css';
    }

    public static function register(): void
    {
        if (! is_file(self::sourcePath())) {
            return;
        }

        self::ensurePublished();

        FilamentAsset::register([
            Css::make(self::STYLE_ID, self::sourcePath()),
        ], self::PACKAGE);
    }

    public static function ensurePublished(): void
    {
        if (! is_file(self::sourcePath())) {
            return;
        }

        $destination = public_path('css/'.self::PACKAGE.'/'.self::STYLE_ID.'.css');

        File::ensureDirectoryExists(dirname($destination));

        if (! is_file($destination) || filemtime(self::sourcePath()) > filemtime($destination)) {
            File::copy(self::sourcePath(), $destination);
        }
    }
}
