<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class ConfigureSubThemesForVpress
{
    /**
     * @param  array{label: string, description?: string, type?: string, capabilities?: list<string>, layouts?: array<string, string>, css?: string}  $definition
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

        $entry = self::formatEntry($id, $definition);
        $pattern = "/('sub_themes'\s*=>\s*\[)(.*?)(\n\s*\],)/s";

        if (preg_match($pattern, $contents, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $insertPosition = $matches[2][1] + strlen($matches[2][0]);
            $updated = substr_replace($contents, $entry, $insertPosition, 0);
        } else {
            $updated = preg_replace(
                '/\n];\s*$/',
                "\n\n    /* Custom visual themes for this application */\n    'sub_themes' => [\n{$entry}    ],\n];\n",
                rtrim($contents)."\n",
                1,
            );

            if (! is_string($updated)) {
                return false;
            }
        }

        File::put($configPath, $updated);

        return true;
    }

    /**
     * @param  array{label: string, description?: string, type?: string, capabilities?: list<string>, layouts?: array<string, string>, css?: string}  $definition
     */
    private static function formatEntry(string $id, array $definition): string
    {
        $label = addslashes($definition['label']);
        $description = addslashes((string) ($definition['description'] ?? ''));
        $type = addslashes((string) ($definition['type'] ?? 'marketing'));
        $pageLayout = $definition['layouts']['page'] ?? "vpress.themes.{$id}.layouts.page";
        $homeLayout = $definition['layouts']['home'] ?? "vpress.themes.{$id}.layouts.home";
        $landingLayout = $definition['layouts']['landing'] ?? "vpress.themes.{$id}.layouts.landing";
        $css = $definition['css'] ?? "resources/vpress/themes/{$id}/theme.css";

        $capabilities = $definition['capabilities'] ?? ['landing'];
        $capabilitiesExport = "['".implode("', '", array_map('addslashes', $capabilities))."']";

        return <<<PHP
        '{$id}' => [
            'label' => '{$label}',
            'description' => '{$description}',
            'type' => '{$type}',
            'capabilities' => {$capabilitiesExport},
            'layouts' => [
                'home' => '{$homeLayout}',
                'landing' => '{$landingLayout}',
                'page' => '{$pageLayout}',
            ],
            'css' => '{$css}',
        ],

PHP;
    }
}
