<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Configure Vite For Voodbuilder.
 */
final class ConfigureViteForVoodbuilder
{
    private const LEGACY_THEME_PATH = 'packages/voodflow/voodbuilder/resources/css/theme.css';

    public static function apply(bool $force = false): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! is_file($viteConfigPath)) {
            return false;
        }

        $contents = File::get($viteConfigPath);
        $original = $contents;

        $contents = str_replace(self::LEGACY_THEME_PATH, VoodbuilderPaths::themeCssRelativePath(), $contents);

        foreach (VoodbuilderPaths::viteInputEntries() as $entry) {
            if (! str_contains($contents, $entry)) {
                $contents = self::appendInputEntry($contents, $entry);
            }
        }

        if (! $force && $contents === $original) {
            return false;
        }

        if ($contents === $original) {
            return false;
        }

        File::put($viteConfigPath, $contents);

        return true;
    }

    private static function appendInputEntry(string $contents, string $entry): string
    {
        if (preg_match("/input:\s*\[(.*?)\]/s", $contents, $matches) !== 1) {
            return $contents;
        }

        $replacement = "input: [{$matches[1]}, '{$entry}']";

        return preg_replace("/input:\s*\[(.*?)\]/s", $replacement, $contents, 1) ?? $contents;
    }
}
