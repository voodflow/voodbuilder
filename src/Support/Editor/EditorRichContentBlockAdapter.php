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

        $stamped = self::stampDynamicAttrsOnSingleRoot($blockId, $encodedConfig, $hydrateSlots, $innerHtml);

        if ($stamped !== null) {
            return $stamped;
        }

        return <<<HTML
<div data-voodbuilder-block="{$blockId}" data-voodbuilder-config="{$encodedConfig}"{$hydrateSlots} class="voodbuilder-editor-dynamic">
{$innerHtml}
</div>
HTML;
    }

    /**
     * Prefer a single outer section/footer/header as the dynamic root so Layers
     * stays flat (no div > section nesting). Falls back to a wrapper div.
     */
    protected static function stampDynamicAttrsOnSingleRoot(
        string $blockId,
        string $encodedConfig,
        string $hydrateSlots,
        string $innerHtml,
    ): ?string {
        $trimmed = ltrim($innerHtml);

        if (! preg_match('/^<(section|footer|header|article)\b([^>]*)>/i', $trimmed, $matches)) {
            return null;
        }

        $tag = strtolower($matches[1]);
        $attrs = $matches[2];

        if (str_contains($attrs, 'data-voodbuilder-block')) {
            return null;
        }

        if (preg_match('/\bclass=(["\'])(.*?)\1/is', $attrs, $classMatch)) {
            $classes = trim($classMatch[2]);

            if (! str_contains($classes, 'voodbuilder-editor-dynamic')) {
                $classes = trim($classes.' voodbuilder-editor-dynamic');
            }

            $attrs = preg_replace(
                '/\bclass=(["\'])(.*?)\1/is',
                'class='.$classMatch[1].$classes.$classMatch[1],
                $attrs,
                1,
            ) ?? $attrs;
        } else {
            $attrs .= ' class="voodbuilder-editor-dynamic"';
        }

        $attrs .= ' data-voodbuilder-block="'.$blockId.'" data-voodbuilder-config="'.$encodedConfig.'"'.$hydrateSlots;

        return '<'.$tag.$attrs.'>'.substr($trimmed, strlen($matches[0]));
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
