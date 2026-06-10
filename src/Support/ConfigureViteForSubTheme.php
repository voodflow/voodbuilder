<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class ConfigureViteForSubTheme
{
    public static function appendCssEntry(string $relativeCssPath): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! is_file($viteConfigPath)) {
            return false;
        }

        $contents = File::get($viteConfigPath);

        if (str_contains($contents, $relativeCssPath)) {
            return false;
        }

        if (preg_match("/input:\s*\[(.*?)\]/s", $contents, $matches) !== 1) {
            return false;
        }

        $replacement = "input: [{$matches[1]}, '{$relativeCssPath}']";
        $updated = preg_replace("/input:\s*\[(.*?)\]/s", $replacement, $contents, 1);

        if (! is_string($updated) || $updated === $contents) {
            return false;
        }

        File::put($viteConfigPath, $updated);

        return true;
    }
}
