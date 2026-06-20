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

        foreach ($decoded as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if (! self::shouldRegisterBlock($definition)) {
                continue;
            }

            $content = self::prepareBlockHtml((string) ($definition['content'] ?? ''));

            $preview = isset($definition['preview'])
                ? self::prepareBlockHtml((string) $definition['preview'])
                : null;

            $registry->register(new GrapesJsBlockDefinition(
                id: (string) ($definition['id'] ?? uniqid('tailblocks-', true)),
                label: self::blockLabel($definition),
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

    /**
     * @param  array<string, mixed>  $definition
     */
    protected static function shouldRegisterBlock(array $definition): bool
    {
        $mode = (string) ($definition['mode'] ?? 'light');
        $modes = (array) config('vpress.grapesjs.tailblocks.modes', ['adaptive']);

        if (in_array('adaptive', $modes, true)) {
            return $mode !== 'dark';
        }

        return in_array($mode, $modes, true);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected static function blockLabel(array $definition): string
    {
        $label = (string) ($definition['label'] ?? 'Block');

        if ((string) ($definition['mode'] ?? '') === 'light') {
            $label = preg_replace('/\s*·\s*light$/i', '', $label) ?? $label;
        }

        return $label;
    }

    protected static function prepareBlockHtml(string $html): string
    {
        return GrapesJsHtmlSanitizer::sanitize(
            TailblocksThemeTokenMigrator::migrateHtml(
                TailwindV4ClassMigrator::migrateHtml($html),
            ),
        );
    }
}
