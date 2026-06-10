<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class ConfigureSubThemesForVpress
{
    /**
     * @param  array{label: string, description?: string, layouts?: array<string, string>, css?: string}  $definition
     */
    public static function registerInConfig(string $id, array $definition): bool
    {
        $configPath = config_path('vpress.php');

        if (! is_file($configPath)) {
            return false;
        }

        $contents = File::get($configPath);
        $needle = "'{$id}' =>";

        if (str_contains($contents, $needle)) {
            return false;
        }

        $label = addslashes($definition['label']);
        $description = addslashes((string) ($definition['description'] ?? ''));
        $pageLayout = $definition['layouts']['page'] ?? "vpress.themes.{$id}.layouts.page";
        $homeLayout = $definition['layouts']['home'] ?? "vpress.themes.{$id}.layouts.home";
        $css = $definition['css'] ?? "resources/vpress/themes/{$id}/theme.css";

        $entry = <<<PHP
        '{$id}' => [
            'label' => '{$label}',
            'description' => '{$description}',
            'layouts' => [
                'home' => '{$homeLayout}',
                'page' => '{$pageLayout}',
            ],
            'css' => '{$css}',
        ],

PHP;

        $pattern = "/('sub_themes'\s*=>\s*\[)(.*?)(\n\s*\],)/s";

        if (preg_match($pattern, $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return false;
        }

        $insertPosition = $matches[2][1] + strlen($matches[2][0]);
        $updated = substr_replace($contents, $entry, $insertPosition, 0);

        File::put($configPath, $updated);

        return true;
    }
}
