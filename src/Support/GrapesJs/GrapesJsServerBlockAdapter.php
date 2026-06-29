<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;

final class GrapesJsServerBlockAdapter
{
    /**
     * @param  class-string<GrapesJsServerBlock>  $blockClass
     */
    public static function toDefinition(
        string $blockClass,
        string $category,
        ?int $eventId = null,
    ): GrapesJsBlockDefinition {
        $blockId = $blockClass::getId();
        $config = self::configFor($blockClass, $eventId);
        $content = self::editorPreviewHtml($blockClass, $config, $eventId);

        if (! SiteFooterBlocks::isFooterBlockId($blockId)) {
            $content = GrapesJsRichContentBlockAdapter::wrap($blockId, $config, $content);
        }

        return new GrapesJsBlockDefinition(
            id: 'voodbuilder-'.$blockId,
            label: $blockClass::getLabel(),
            category: $category,
            content: $content,
            preview: GrapesJsBlockThumbnail::forBlockId($blockId),
            attributes: [
                'class' => 'voodbuilder-gjs-dynamic',
                'title' => $blockClass::getLabel(),
            ],
        );
    }

    /**
     * @param  class-string<GrapesJsServerBlock>  $blockClass
     * @return array<string, mixed>
     */
    public static function configFor(string $blockClass, ?int $eventId = null): array
    {
        $config = $blockClass::defaultConfig();

        return GrapesJsDefaultBlockConfig::mergeEventId($config, $blockClass::getId(), $eventId);
    }

    /**
     * @param  class-string<GrapesJsServerBlock>  $blockClass
     * @param  array<string, mixed>  $config
     */
    public static function editorPreviewHtml(string $blockClass, array $config, ?int $eventId = null): string
    {
        $context = GrapesJsRichContentBlockAdapter::renderData($eventId);

        return $blockClass::toPreviewHtml($config, $context);
    }
}
