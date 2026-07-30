<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

/**
 * Editor Rich Content Block Adapter.
 */
final class EditorRichContentBlockAdapter
{
    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public static function toDefinition(
        string $blockClass,
        string $category,
        ?int $eventId = null,
    ): EditorBlockDefinition {
        $blockId = $blockClass::getId();
        $config = EditorDefaultBlockConfig::for($blockClass, $eventId);
        $editorInner = self::editorPreviewHtml($blockClass, $config);
        $previewHtml = EditorBlockPreview::wrapHtml(self::prepareBlockHtml($editorInner));

        return new EditorBlockDefinition(
            id: 'voodbuilder-'.$blockId,
            label: $blockClass::getLabel(),
            category: $category,
            content: self::wrap($blockId, $config, $editorInner),
            preview: $previewHtml ?? EditorBlockThumbnail::forBlockId($blockId),
            attributes: [
                'class' => 'voodbuilder-editor-dynamic',
                'title' => $blockClass::getLabel(),
            ],
        );
    }

    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     * @param  array<string, mixed>  $config
     */
    public static function editorPreviewHtml(string $blockClass, array $config): string
    {
        if (method_exists($blockClass, 'toPreviewHtml')) {
            try {
                return $blockClass::toPreviewHtml($config);
            } catch (\Throwable) {
                return self::fallbackEditorHtml($blockClass::getLabel());
            }
        }

        return self::fallbackEditorHtml($blockClass::getLabel());
    }

    public static function prepareBlockHtml(string $html): string
    {
        return EditorSmartButtonAnnotator::annotate(
            EditorPlaceholderNormalizer::normalizeHtml(
                VoodbuilderThemeTokenMigrator::migrateHtml(
                    TailwindV4ClassMigrator::migrateHtml($html),
                ),
            ),
        );
    }

    public static function fallbackEditorHtml(string $label): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_HTML5);

        return '<div class="voodbuilder-editor-dynamic-placeholder rounded-lg border border-dashed border-vp-divider bg-vp-bg-alt p-6 text-center text-sm text-vp-text-2">'.$safeLabel.'</div>';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function wrap(string $blockId, array $config, string $innerHtml): string
    {
        $innerHtml = self::prepareBlockHtml($innerHtml);

        $encodedConfig = htmlspecialchars(
            json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ENT_QUOTES | ENT_HTML5,
        );

        $hydrateSlots = SiteFooterBlocks::isFooterBlockId($blockId) ? ' data-voodbuilder-hydrate-slots="1"' : '';

        return <<<HTML
<div data-voodbuilder-block="{$blockId}" data-voodbuilder-config="{$encodedConfig}"{$hydrateSlots} class="voodbuilder-editor-dynamic">
{$innerHtml}
</div>
HTML;
    }

    public static function previewMedia(string $html, string $label): string
    {
        $migrated = self::prepareBlockHtml($html);

        return '<div class="voodbuilder-editor-block-preview"><div class="voodbuilder-editor-block-preview__scale">'.$migrated.'</div></div>';
    }

    /**
     * @return array<string, mixed>
     */
    public static function renderData(?int $eventId = null): array
    {
        return array_filter([
            'event_id' => $eventId,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
