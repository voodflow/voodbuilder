<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Vpress\Contracts\GrapesJsServerBlock;

final class GrapesJsBlockPreview
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function render(string $blockId, array $config, ?int $eventId = null): ?string
    {
        $richBlockClass = app(GrapesJsDynamicBlockRegistry::class)->resolve($blockId);

        if ($richBlockClass !== null) {
            return self::renderRichBlock($richBlockClass, $config, $eventId);
        }

        $serverBlockClass = app(GrapesJsServerBlockRegistry::class)->resolve($blockId);

        if ($serverBlockClass !== null) {
            return self::renderServerBlock($serverBlockClass, $config, $eventId);
        }

        return null;
    }

    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     * @param  array<string, mixed>  $config
     */
    protected static function renderRichBlock(string $blockClass, array $config, ?int $eventId): string
    {
        if ($eventId !== null && GrapesJsDefaultBlockConfig::needsEventId($blockClass::getId()) && empty($config['event_id'])) {
            $config['event_id'] = $eventId;
        }

        return GrapesJsRichContentBlockAdapter::wrap(
            $blockClass::getId(),
            $config,
            GrapesJsRichContentBlockAdapter::editorPreviewHtml($blockClass, $config),
        );
    }

    /**
     * @param  class-string<GrapesJsServerBlock>  $blockClass
     * @param  array<string, mixed>  $config
     */
    protected static function renderServerBlock(string $blockClass, array $config, ?int $eventId): string
    {
        $blockId = $blockClass::getId();
        $mergedConfig = GrapesJsDefaultBlockConfig::mergeEventId(
            $config !== [] ? $config : $blockClass::defaultConfig(),
            $blockId,
            $eventId,
        );
        $context = GrapesJsRichContentBlockAdapter::renderData($eventId);
        $html = $blockClass::toPreviewHtml($mergedConfig, $context);

        if (SiteFooterBlocks::isFooterBlockId($blockId)) {
            return GrapesJsRichContentBlockAdapter::prepareBlockHtml($html);
        }

        return GrapesJsRichContentBlockAdapter::wrap($blockId, $mergedConfig, $html);
    }
}
