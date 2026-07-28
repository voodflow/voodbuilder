<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Publishes the animated VoodBuilder brand mark for use as the default site logo.
 */
final class BrandMarkAssets
{
    public const PUBLIC_RELATIVE = 'vendor/voodbuilder/voodbuilder-mark.svg';

    public static function sourcePath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/images/voodbuilder-mark.svg';
    }

    public static function publicPath(): string
    {
        return public_path(self::PUBLIC_RELATIVE);
    }

    /**
     * Absolute URL to the animated mark (SMIL SVG). Ensures the file is published.
     */
    public static function url(): string
    {
        self::ensurePublished();

        return asset(self::PUBLIC_RELATIVE);
    }

    public static function ensurePublished(): void
    {
        if (! is_file(self::sourcePath())) {
            return;
        }

        $destination = self::publicPath();

        File::ensureDirectoryExists(dirname($destination));

        if (! is_file($destination) || filemtime(self::sourcePath()) > filemtime($destination)) {
            File::copy(self::sourcePath(), $destination);
        }
    }
}
