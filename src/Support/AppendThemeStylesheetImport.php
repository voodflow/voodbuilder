<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Registers a CSS file as a Vite input so it can be loaded on demand
 * via EditorAssets::pageViteEntries() (not bundled into theme.css).
 */
final class AppendThemeStylesheetImport
{
    public static function append(string $absoluteCssPath): bool
    {
        $relative = VoodbuilderPaths::relativeToBasePath($absoluteCssPath);

        if ($relative === '' || ! is_file($absoluteCssPath)) {
            return false;
        }

        $viteConfig = base_path('vite.config.js');

        if (! is_file($viteConfig)) {
            return false;
        }

        $contents = File::get($viteConfig);
        $needle = "'{$relative}'";
        $doubleNeedle = "\"{$relative}\"";

        if (str_contains($contents, $needle) || str_contains($contents, $doubleNeedle)) {
            return false;
        }

        // Insert after the main theme.css input when present; otherwise after `input: [`.
        $themeEntry = VoodbuilderPaths::themeCssRelativePath();
        $insertLine = "                '{$relative}',";

        if (preg_match('/([\'"])'.preg_quote($themeEntry, '/').'\1,?\s*\n/', $contents, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $match = $matches[0];
            $offset = $match[1] + strlen($match[0]);
            $updated = substr($contents, 0, $offset).$insertLine."\n".substr($contents, $offset);
            File::put($viteConfig, $updated);

            return true;
        }

        if (preg_match('/(input:\s*\[\s*\n)/', $contents, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $match = $matches[1];
            $offset = $match[1] + strlen($match[0]);
            $updated = substr($contents, 0, $offset).$insertLine."\n".substr($contents, $offset);
            File::put($viteConfig, $updated);

            return true;
        }

        return false;
    }
}
