<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class ConfigureViteForVpress
{
    private const LEGACY_THEME_PATH = 'packages/voodflow/vpress/resources/css/theme.css';

    public static function apply(bool $force = false): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! is_file($viteConfigPath)) {
            return false;
        }

        $themePath = VpressPaths::themeCssRelativePath();
        $contents = File::get($viteConfigPath);
        $original = $contents;

        $contents = str_replace(self::LEGACY_THEME_PATH, $themePath, $contents);

        if (! str_contains($contents, $themePath)) {
            $contents = self::appendThemeEntry($contents, $themePath);
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

    private static function appendThemeEntry(string $contents, string $themePath): string
    {
        if (preg_match("/input:\s*\[(.*?)\]/s", $contents, $matches) !== 1) {
            return $contents;
        }

        $replacement = "input: [{$matches[1]}, '{$themePath}']";

        return preg_replace("/input:\s*\[(.*?)\]/s", $replacement, $contents, 1) ?? $contents;
    }
}
