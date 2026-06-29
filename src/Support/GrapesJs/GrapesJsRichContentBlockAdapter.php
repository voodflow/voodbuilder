<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

final class GrapesJsRichContentBlockAdapter
{
    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public static function toDefinition(
        string $blockClass,
        string $category,
        ?int $eventId = null,
    ): GrapesJsBlockDefinition {
        $blockId = $blockClass::getId();
        $config = GrapesJsDefaultBlockConfig::for($blockClass, $eventId);
        $editorInner = self::editorPreviewHtml($blockClass, $config);

        return new GrapesJsBlockDefinition(
            id: 'voodbuilder-'.$blockId,
            label: $blockClass::getLabel(),
            category: $category,
            content: self::wrap($blockId, $config, $editorInner),
            preview: GrapesJsBlockThumbnail::forBlockId($blockId),
            attributes: [
                'class' => 'voodbuilder-gjs-dynamic',
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
            return $blockClass::toPreviewHtml($config);
        }

        return self::fallbackEditorHtml($blockClass::getLabel());
    }

    public static function prepareBlockHtml(string $html): string
    {
        return GrapesJsPlaceholderNormalizer::normalizeHtml(
            TailblocksThemeTokenMigrator::migrateHtml(
                TailwindV4ClassMigrator::migrateHtml($html),
            ),
        );
    }

    public static function fallbackEditorHtml(string $label): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_HTML5);

        return '<div class="voodbuilder-gjs-dynamic-placeholder rounded-lg border border-dashed border-vp-divider bg-vp-bg-alt p-6 text-center text-sm text-vp-text-2">'.$safeLabel.'</div>';
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
<div data-voodbuilder-block="{$blockId}" data-voodbuilder-config="{$encodedConfig}"{$hydrateSlots} class="voodbuilder-gjs-dynamic">
{$innerHtml}
</div>
HTML;
    }

    public static function previewMedia(string $html, string $label): string
    {
        $migrated = self::prepareBlockHtml($html);

        return '<div class="voodbuilder-gjs-block-preview"><div class="voodbuilder-gjs-block-preview__scale">'.$migrated.'</div></div>';
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
