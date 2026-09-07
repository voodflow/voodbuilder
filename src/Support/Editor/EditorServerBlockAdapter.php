<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Contracts\EditorServerBlock;

/**
 * Editor Server Block Adapter.
 */
final class EditorServerBlockAdapter
{
    /**
     * @param  class-string<EditorServerBlock>  $blockClass
     */
    public static function toDefinition(
        string $blockClass,
        string $category,
        ?int $eventId = null,
    ): EditorBlockDefinition {
        $blockId = $blockClass::getId();
        $config = self::configFor($blockClass, $eventId);
        $content = self::editorPreviewHtml($blockClass, $config, $eventId);

        if (! SiteFooterBlocks::isFooterBlockId($blockId)) {
            $content = EditorRichContentBlockAdapter::wrap($blockId, $config, $content);
        }

        return new EditorBlockDefinition(
            id: 'voodbuilder-'.$blockId,
            label: $blockClass::getLabel(),
            category: $category,
            content: $content,
            preview: self::sidebarPreviewFor($blockId, $content),
            attributes: [
                'class' => 'voodbuilder-editor-dynamic',
                'title' => $blockClass::getLabel(),
            ],
        );
    }

    /**
     * @param  class-string<EditorServerBlock>  $blockClass
     * @return array<string, mixed>
     */
    public static function configFor(string $blockClass, ?int $eventId = null): array
    {
        $config = $blockClass::defaultConfig();

        return EditorDefaultBlockConfig::mergeEventId($config, $blockClass::getId(), $eventId);
    }

    /**
     * @param  class-string<EditorServerBlock>  $blockClass
     * @param  array<string, mixed>  $config
     */
    public static function editorPreviewHtml(string $blockClass, array $config, ?int $eventId = null): string
    {
        $context = EditorRichContentBlockAdapter::renderData($eventId);

        return $blockClass::toPreviewHtml($config, $context);
    }

    private static function sidebarPreviewFor(string $blockId, string $content): string
    {
        if (SiteFooterBlocks::isFooterBlockId($blockId) || SiteNavBlocks::isNavBlockId($blockId)) {
            return EditorSiteChromeSidebarPreview::fromPreviewHtml($content, $blockId);
        }

        return EditorBlockPreview::wrapHtml($content) ?? EditorBlockThumbnail::forBlockId($blockId);
    }
}
