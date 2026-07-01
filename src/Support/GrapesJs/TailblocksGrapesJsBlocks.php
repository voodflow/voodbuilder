<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

/**
 * @deprecated Use VoodbuilderSectionGrapesJsBlocks — only kept for voodbuilder:build-sections intermediate export.
 */
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
                attributes: [
                    'title' => self::blockLabel($definition),
                ],
            ));
        }
    }

    public static function catalogPath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/grapesjs/tailblocks-blocks.json';
    }

    public static function utilitiesCssEntry(): string
    {
        return VoodbuilderPaths::relativeToBasePath(
            VoodbuilderPaths::packagePath().'/resources/css/grapesjs/tailblocks-utilities.css',
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
        if (config('voodbuilder.grapesjs.voodbuilder_footers.enabled', true)) {
            $label = (string) ($definition['label'] ?? '');

            if (str_starts_with($label, 'Footer ')) {
                return false;
            }
        }

        $mode = (string) ($definition['mode'] ?? 'light');
        $modes = (array) config('voodbuilder.grapesjs.tailblocks.modes', ['adaptive']);

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
            GrapesJsPlaceholderNormalizer::normalizeHtml(
                TailblocksThemeTokenMigrator::migrateHtml(
                    TailwindV4ClassMigrator::migrateHtml($html),
                ),
            ),
        );
    }
}
