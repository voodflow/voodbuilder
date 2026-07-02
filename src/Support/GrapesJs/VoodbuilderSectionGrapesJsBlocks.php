<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

final class VoodbuilderSectionGrapesJsBlocks
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

            $registry->register(new GrapesJsBlockDefinition(
                id: (string) ($definition['id'] ?? uniqid('vb-', true)),
                label: self::blockLabel($definition),
                category: (string) ($definition['category'] ?? 'Sections'),
                content: $content,
                preview: null,
                attributes: [
                    'title' => self::blockLabel($definition),
                ],
            ));
        }
    }

    public static function catalogPath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/grapesjs/section-blocks.json';
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
        if (config('voodbuilder.grapesjs.site_blocks.hide_section_chrome', true)) {
            $id = (string) ($definition['id'] ?? '');

            if (str_contains($id, '-header-') || str_contains($id, '-footer-')) {
                return false;
            }
        }

        if (config('voodbuilder.grapesjs.voodbuilder_footers.enabled', true)) {
            $id = (string) ($definition['id'] ?? '');

            if (str_contains($id, '-footer-')) {
                return false;
            }
        }

        $mode = (string) ($definition['mode'] ?? 'light');
        $modes = (array) config('voodbuilder.grapesjs.sections.modes', ['adaptive']);

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
        return (string) ($definition['label'] ?? 'Section');
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
