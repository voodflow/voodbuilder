<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Contracts\EditorConfigurableBlock;

final class EditorBlockPreview
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function render(string $blockId, array $config = []): ?string
    {
        try {
            return self::renderWrappedBlock($blockId, $config);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function renderWrappedBlock(string $blockId, array $config): ?string
    {
        $context = EditorRichContentBlockAdapter::renderData(null);

        $dynamicRegistry = app(EditorDynamicBlockRegistry::class);
        $richBlockClass = $dynamicRegistry->resolve($blockId);

        if ($richBlockClass !== null) {
            $mergedConfig = $config !== [] ? $config : EditorDefaultBlockConfig::for($richBlockClass, null);
            $inner = EditorRichContentBlockAdapter::editorPreviewHtml($richBlockClass, $mergedConfig);

            return EditorRichContentBlockAdapter::wrap($blockId, $mergedConfig, $inner);
        }

        $serverRegistry = app(EditorServerBlockRegistry::class);
        $serverBlockClass = $serverRegistry->resolve($blockId);

        if ($serverBlockClass === null) {
            return null;
        }

        $mergedConfig = $config !== [] ? $config : $serverBlockClass::defaultConfig();

        if ($config !== [] && is_subclass_of($serverBlockClass, EditorConfigurableBlock::class)) {
            $mergedConfig = $serverBlockClass::normalizeConfig($config);
        }
        $inner = $serverBlockClass::toPreviewHtml($mergedConfig, $context);
        $inner = EditorRichContentBlockAdapter::prepareBlockHtml($inner);

        return EditorRichContentBlockAdapter::wrap($blockId, $mergedConfig, $inner);
    }

    public static function wrapHtml(string $html): ?string
    {
        if ($html === '') {
            return null;
        }

        return '<div class="voodbuilder-editor-block-preview"><div class="voodbuilder-editor-block-preview__scale">'.$html.'</div></div>';
    }

    public static function wrapSiteChromeHtml(string $html, string $blockId = ''): ?string
    {
        if ($html === '') {
            return null;
        }

        $navModifier = SiteNavBlocks::isNavBlockId($blockId)
            ? ' voodbuilder-editor-block-preview--nav'
            : '';

        return '<div class="voodbuilder-editor-block-preview voodbuilder-editor-block-preview--site'.$navModifier.'"><div class="voodbuilder-editor-block-preview__scale">'.$html.'</div></div>';
    }
}
