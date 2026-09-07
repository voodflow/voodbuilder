<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Publishes the animated VoodBuilder / Voodflow brand mark for default logo + favicon.
 */
final class BrandMarkAssets
{
    public const PUBLIC_RELATIVE = 'vendor/voodbuilder/voodbuilder-mark.svg';

    public const PUBLIC_PNG_RELATIVE = 'vendor/voodbuilder/voodbuilder-mark.png';

    public static function sourcePath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/images/voodbuilder-mark.svg';
    }

    public static function sourcePngPath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/images/voodbuilder-mark.png';
    }

    public static function publicPath(): string
    {
        return public_path(self::PUBLIC_RELATIVE);
    }

    public static function publicPngPath(): string
    {
        return public_path(self::PUBLIC_PNG_RELATIVE);
    }

    /**
     * Absolute URL to the animated mark (SMIL SVG). Ensures the file is published.
     */
    public static function url(): string
    {
        self::ensurePublished();

        return asset(self::PUBLIC_RELATIVE);
    }

    /**
     * Prefer SVG for `<link rel="icon">`; PNG is published for `/favicon.ico` fallbacks.
     */
    public static function faviconUrl(): string
    {
        return self::url();
    }

    public static function ensurePublished(): void
    {
        self::publishAsset(self::sourcePath(), self::publicPath());
        self::publishAsset(self::sourcePngPath(), self::publicPngPath());
        self::replaceEmptyLaravelFavicon();
    }

    /**
     * Browsers still request `/favicon.ico` when no custom upload exists. Replace the
     * empty Laravel skeleton file with the Voodflow mark so the tab is never blank/Laravel.
     */
    private static function replaceEmptyLaravelFavicon(): void
    {
        $pngSource = self::sourcePngPath();

        if (! is_file($pngSource)) {
            return;
        }

        $faviconPath = public_path('favicon.ico');

        if (is_file($faviconPath) && filesize($faviconPath) > 0) {
            // Keep a real custom/app favicon.ico; only replace the empty Laravel placeholder.
            return;
        }

        File::copy($pngSource, $faviconPath);
    }

    private static function publishAsset(string $source, string $destination): void
    {
        if (! is_file($source)) {
            return;
        }

        File::ensureDirectoryExists(dirname($destination));

        if (! is_file($destination) || filemtime($source) > filemtime($destination)) {
            File::copy($source, $destination);
        }
    }
}
