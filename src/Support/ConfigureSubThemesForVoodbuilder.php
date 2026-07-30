<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Configure Sub Themes For Voodbuilder.
 */
final class ConfigureSubThemesForVoodbuilder
{
    /**
     * @param  array{label: string, description?: string, type?: string, capabilities?: list<string>, layouts?: array<string, string>, css?: string, chrome?: array<string, bool>}  $definition
     */
    public static function registerInConfig(string $id, array $definition): bool
    {
        $configPath = config_path('voodbuilder.php');

        if (! is_file($configPath)) {
            return false;
        }

        $subThemes = self::parseSubThemesFromConfigFile($configPath);

        if (array_key_exists($id, $subThemes)) {
            return false;
        }

        $subThemes[$id] = self::normalizeStoredDefinition($id, $definition);

        return self::writeSubThemes($configPath, $subThemes);
    }

    /**
     * @param  array{label: string, description?: string, type?: string, capabilities?: list<string>, layouts?: array<string, string>, css?: string, chrome?: array<string, bool>}  $definition
     */
    public static function upsertInConfig(string $id, array $definition): bool
    {
        $configPath = config_path('voodbuilder.php');

        if (! is_file($configPath)) {
            return false;
        }

        $subThemes = self::parseSubThemesFromConfigFile($configPath);
        $subThemes[$id] = self::normalizeStoredDefinition($id, $definition);

        return self::writeSubThemes($configPath, $subThemes);
    }

    public static function removeFromConfig(string $id): bool
    {
        $configPath = config_path('voodbuilder.php');

        if (! is_file($configPath)) {
            return false;
        }

        $subThemes = self::parseSubThemesFromConfigFile($configPath);

        if (! array_key_exists($id, $subThemes)) {
            return false;
        }

        unset($subThemes[$id]);

        return self::writeSubThemes($configPath, $subThemes);
    }

    /**
     * @param  array<string, array<string, mixed>>  $subThemes
     */
    protected static function writeSubThemes(string $configPath, array $subThemes): bool
    {
        $contents = File::get($configPath);
        $updated = self::replaceSubThemesBlock($contents, $subThemes);

        File::put($configPath, $updated);

        return true;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function parseSubThemesFromConfigFile(string $configPath): array
    {
        /** @var array<string, mixed> $config */
        $config = require $configPath;
        $subThemes = $config['sub_themes'] ?? [];

        if (! is_array($subThemes)) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $normalized */
        $normalized = [];

        foreach ($subThemes as $id => $definition) {
            if (! is_string($id) || ! is_array($definition)) {
                continue;
            }

            $normalized[$id] = $definition;
        }

        return $normalized;
    }

    /**
     * @param  array<string, array<string, mixed>>  $subThemes
     */
    protected static function replaceSubThemesBlock(string $contents, array $subThemes): string
    {
        $bounds = self::locateSubThemesArrayBounds($contents);

        if ($bounds === null) {
            $exported = self::exportSubThemes($subThemes);

            $updated = preg_replace(
                '/\n];\s*$/',
                "\n\n    /* Custom visual themes for this application */\n    'sub_themes' => [\n{$exported}    ],\n];\n",
                rtrim($contents)."\n",
                1,
            );

            if (! is_string($updated)) {
                throw new \RuntimeException('Unable to update config/voodbuilder.php.');
            }

            return $updated;
        }

        $exported = self::exportSubThemes($subThemes);

        return substr($contents, 0, $bounds['open'] + 1)
            ."\n".$exported.'    '
            .substr($contents, $bounds['close']);
    }

    /**
     * @return array{open: int, close: int}|null
     */
    protected static function locateSubThemesArrayBounds(string $contents): ?array
    {
        if (preg_match("/'sub_themes'\s*=>\s*\[/", $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $openBracket = strpos($contents, '[', $matches[0][1]);

        if ($openBracket === false) {
            return null;
        }

        $closeBracket = self::findMatchingCloseBracket($contents, $openBracket);

        return [
            'open' => $openBracket,
            'close' => $closeBracket,
        ];
    }

    protected static function findMatchingCloseBracket(string $contents, int $openPos): int
    {
        $depth = 0;
        $length = strlen($contents);
        $inString = false;
        $stringChar = '';

        for ($index = $openPos; $index < $length; $index++) {
            $character = $contents[$index];

            if ($inString) {
                if ($character === '\\' && $index + 1 < $length) {
                    $index++;

                    continue;
                }

                if ($character === $stringChar) {
                    $inString = false;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $inString = true;
                $stringChar = $character;

                continue;
            }

            if ($character === '[') {
                $depth++;
            } elseif ($character === ']') {
                $depth--;

                if ($depth === 0) {
                    return $index;
                }
            }
        }

        throw new \RuntimeException('Unbalanced array brackets in config/voodbuilder.php.');
    }

    /**
     * @param  array<string, array<string, mixed>>  $subThemes
     */
    protected static function exportSubThemes(array $subThemes): string
    {
        if ($subThemes === []) {
            return '';
        }

        $entries = [];

        foreach ($subThemes as $id => $definition) {
            $entries[] = rtrim(self::formatEntry($id, $definition));
        }

        return implode("\n", $entries)."\n";
    }

    /**
     * @param  array{label: string, description?: string, type?: string, capabilities?: list<string>, layouts?: array<string, string>, css?: string, chrome?: array<string, bool>}  $definition
     * @return array{label: string, description: string, type: string, capabilities: list<string>, layouts: array<string, string>, css: string, chrome?: array<string, bool>}
     */
    protected static function normalizeStoredDefinition(string $id, array $definition): array
    {
        $capabilities = $definition['capabilities'] ?? ['landing'];
        $capabilities = is_array($capabilities) ? array_values(array_filter($capabilities, is_string(...))) : ['landing'];

        $layouts = is_array($definition['layouts'] ?? null) ? $definition['layouts'] : [];

        return [
            'label' => (string) $definition['label'],
            'description' => (string) ($definition['description'] ?? ''),
            'type' => (string) ($definition['type'] ?? 'marketing'),
            'capabilities' => $capabilities !== [] ? $capabilities : ['landing'],
            'layouts' => [
                'home' => (string) ($layouts['home'] ?? "voodbuilder.themes.{$id}.layouts.home"),
                'landing' => (string) ($layouts['landing'] ?? "voodbuilder.themes.{$id}.layouts.landing"),
                'page' => (string) ($layouts['page'] ?? "voodbuilder.themes.{$id}.layouts.page"),
                ...array_filter([
                    'article' => is_string($layouts['article'] ?? null) ? $layouts['article'] : null,
                    'section_index' => is_string($layouts['section_index'] ?? null) ? $layouts['section_index'] : null,
                ], fn (?string $layout): bool => is_string($layout) && $layout !== ''),
            ],
            'css' => (string) ($definition['css'] ?? "resources/voodbuilder/themes/{$id}/theme.css"),
            ...(
                is_array($definition['chrome'] ?? null) && $definition['chrome'] !== []
                    ? ['chrome' => $definition['chrome']]
                    : []
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function formatEntry(string $id, array $definition): string
    {
        $normalized = self::normalizeStoredDefinition($id, [
            'label' => (string) ($definition['label'] ?? $id),
            'description' => (string) ($definition['description'] ?? ''),
            'type' => (string) ($definition['type'] ?? 'marketing'),
            'capabilities' => is_array($definition['capabilities'] ?? null) ? $definition['capabilities'] : ['landing'],
            'layouts' => is_array($definition['layouts'] ?? null) ? $definition['layouts'] : [],
            'css' => (string) ($definition['css'] ?? "resources/voodbuilder/themes/{$id}/theme.css"),
            'chrome' => is_array($definition['chrome'] ?? null) ? $definition['chrome'] : null,
        ]);

        $label = addslashes($normalized['label']);
        $description = addslashes($normalized['description']);
        $type = addslashes($normalized['type']);
        $pageLayout = addslashes($normalized['layouts']['page']);
        $homeLayout = addslashes($normalized['layouts']['home']);
        $landingLayout = addslashes($normalized['layouts']['landing']);
        $css = addslashes($normalized['css']);

        $capabilitiesExport = "['".implode("', '", array_map('addslashes', $normalized['capabilities']))."']";

        $chromeBlock = '';

        if (is_array($normalized['chrome'] ?? null) && $normalized['chrome'] !== []) {
            $chromeLines = [];

            foreach ($normalized['chrome'] as $flag => $value) {
                if (! is_string($flag)) {
                    continue;
                }

                $chromeLines[] = "                '{$flag}' => ".($value ? 'true' : 'false').',';
            }

            if ($chromeLines !== []) {
                $chromeBlock = "\n            'chrome' => [\n".implode("\n", $chromeLines)."\n            ],";
            }
        }

        $extraLayouts = '';

        if (isset($normalized['layouts']['article']) && is_string($normalized['layouts']['article'])) {
            $extraLayouts .= "\n                'article' => '".addslashes($normalized['layouts']['article'])."',";
        }

        if (isset($normalized['layouts']['section_index']) && is_string($normalized['layouts']['section_index'])) {
            $extraLayouts .= "\n                'section_index' => '".addslashes($normalized['layouts']['section_index'])."',";
        }

        return <<<PHP
        '{$id}' => [
            'label' => '{$label}',
            'description' => '{$description}',
            'type' => '{$type}',
            'capabilities' => {$capabilitiesExport},
            'layouts' => [
                'home' => '{$homeLayout}',
                'landing' => '{$landingLayout}',
                'page' => '{$pageLayout}',{$extraLayouts}
            ],
            'css' => '{$css}',{$chromeBlock}
        ],

PHP;
    }
}
