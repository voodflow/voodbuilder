<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

/**
 * Voodbuilder Section Editor Blocks.
 */
final class VoodbuilderSectionEditorBlocks
{
    public static function register(EditorBlockRegistry $registry, ?string $theme = null): void
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

            $content = self::prepareBlockHtml(
                (string) ($definition['content'] ?? ''),
                (string) ($definition['id'] ?? ''),
            );

            $registry->register(new EditorBlockDefinition(
                id: (string) ($definition['id'] ?? uniqid('vb-', true)),
                label: self::blockLabel($definition),
                category: (string) ($definition['category'] ?? 'Sections'),
                content: $content,
                preview: EditorBlockPreview::wrapHtml($content),
                attributes: [
                    'title' => self::blockLabel($definition),
                ],
            ));
        }
    }

    public static function catalogPath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/editor/section-blocks.json';
    }

    public static function utilitiesCssEntry(): string
    {
        return VoodbuilderPaths::relativeToBasePath(
            VoodbuilderPaths::packagePath().'/resources/css/editor/section-utilities.css',
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
        $id = (string) ($definition['id'] ?? '');

        if ($id !== '' && SectionItemCountAnnotator::isRedundant($id)) {
            return false;
        }

        $excluded = array_map('strval', (array) config('voodbuilder.editor.excluded_editor_blocks', []));

        if ($id !== '' && in_array($id, $excluded, true)) {
            return false;
        }

        if (config('voodbuilder.editor.site_blocks.hide_section_chrome', true)) {
            if (str_contains($id, '-header-') || str_contains($id, '-footer-')) {
                return false;
            }
        }

        if (config('voodbuilder.editor.voodbuilder_footers.enabled', true)) {
            if (str_contains($id, '-footer-')) {
                return false;
            }
        }

        $mode = (string) ($definition['mode'] ?? 'light');
        $modes = (array) config('voodbuilder.editor.sections.modes', ['adaptive']);

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

    protected static function prepareBlockHtml(string $html, string $blockId = ''): string
    {
        $html = EditorHtmlSanitizer::sanitize(
            EditorPlaceholderNormalizer::normalizeHtml(
                VoodbuilderThemeTokenMigrator::migrateHtml(
                    TailwindV4ClassMigrator::migrateHtml($html),
                ),
            ),
        );
        $html = EditorSmartButtonAnnotator::annotate($html);

        if ($blockId !== '' && preg_match('/<section\b/i', $html) === 1) {
            $attribute = ' data-voodbuilder-section-block="'.htmlspecialchars($blockId, ENT_QUOTES, 'UTF-8').'"';

            $html = (string) preg_replace('/<section\b/i', '<section'.$attribute, $html, 1);
        }

        if ($blockId !== '') {
            $html = SectionItemCountAnnotator::annotate($html, $blockId);
        }

        return $html;
    }
}
