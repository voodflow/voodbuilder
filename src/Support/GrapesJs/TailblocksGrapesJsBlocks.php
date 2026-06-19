<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Support\VpressPaths;

final class TailblocksGrapesJsBlocks
{
    public static function register(GrapesJsBlockRegistry $registry, ?string $theme = null): void
    {
        $catalogPath = self::catalogPath();

        if (! is_file($catalogPath)) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($catalogPath), true);

        if (! is_array($decoded)) {
            return;
        }

        $preferredTheme = $theme ?? (string) config('vpress.grapesjs.tailblocks.theme', 'indigo');
        $modes = (array) config('vpress.grapesjs.tailblocks.modes', ['light', 'dark']);

        foreach ($decoded as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $mode = (string) ($definition['mode'] ?? 'light');

            if (! in_array($mode, $modes, true)) {
                continue;
            }

            $content = GrapesJsHtmlSanitizer::sanitize(
                TailwindV4ClassMigrator::migrateHtml(
                    self::applyThemeToHtml((string) ($definition['content'] ?? ''), $preferredTheme),
                ),
            );

            $preview = isset($definition['preview'])
                ? GrapesJsHtmlSanitizer::sanitize(
                    TailwindV4ClassMigrator::migrateHtml(self::applyThemeToHtml((string) $definition['preview'], $preferredTheme)),
                )
                : null;

            $registry->register(new GrapesJsBlockDefinition(
                id: (string) ($definition['id'] ?? uniqid('tailblocks-', true)),
                label: (string) ($definition['label'] ?? 'Block'),
                category: (string) ($definition['category'] ?? 'Tailblocks'),
                content: $content,
                preview: $preview,
            ));
        }
    }

    public static function catalogPath(): string
    {
        return VpressPaths::packagePath().'/resources/grapesjs/tailblocks-blocks.json';
    }

    public static function utilitiesCssEntry(): string
    {
        return VpressPaths::relativeToBasePath(
            VpressPaths::packagePath().'/resources/css/grapesjs/tailblocks-utilities.css',
        );
    }

    public static function isAvailable(): bool
    {
        return is_file(self::catalogPath());
    }

    protected static function applyThemeToHtml(string $html, string $theme): string
    {
        $allowedThemes = ['indigo', 'yellow', 'red', 'purple', 'pink', 'blue', 'green'];

        if (! in_array($theme, $allowedThemes, true)) {
            return $html;
        }

        return preg_replace(
            '/\b(bg|text|border|ring|from|to|via)-(indigo|yellow|red|purple|pink|blue|green)-/i',
            '$1-'.$theme.'-',
            $html,
        ) ?? $html;
    }
}
