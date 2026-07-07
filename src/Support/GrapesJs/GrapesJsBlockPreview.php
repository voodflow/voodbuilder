<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsConfigurableBlock;

final class GrapesJsBlockPreview
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
        $context = GrapesJsRichContentBlockAdapter::renderData(null);

        $dynamicRegistry = app(GrapesJsDynamicBlockRegistry::class);
        $richBlockClass = $dynamicRegistry->resolve($blockId);

        if ($richBlockClass !== null) {
            $mergedConfig = $config !== [] ? $config : GrapesJsDefaultBlockConfig::for($richBlockClass, null);
            $inner = GrapesJsRichContentBlockAdapter::editorPreviewHtml($richBlockClass, $mergedConfig);

            return GrapesJsRichContentBlockAdapter::wrap($blockId, $mergedConfig, $inner);
        }

        $serverRegistry = app(GrapesJsServerBlockRegistry::class);
        $serverBlockClass = $serverRegistry->resolve($blockId);

        if ($serverBlockClass === null) {
            return null;
        }

        $mergedConfig = $config !== [] ? $config : $serverBlockClass::defaultConfig();

        if ($config !== [] && is_subclass_of($serverBlockClass, GrapesJsConfigurableBlock::class)) {
            $mergedConfig = $serverBlockClass::normalizeConfig($config);
        }
        $inner = $serverBlockClass::toPreviewHtml($mergedConfig, $context);
        $inner = GrapesJsRichContentBlockAdapter::prepareBlockHtml($inner);

        return GrapesJsRichContentBlockAdapter::wrap($blockId, $mergedConfig, $inner);
    }

    public static function wrapHtml(string $html): ?string
    {
        if ($html === '') {
            return null;
        }

        return '<div class="voodbuilder-gjs-block-preview"><div class="voodbuilder-gjs-block-preview__scale">'.$html.'</div></div>';
    }

    public static function wrapSiteChromeHtml(string $html, string $blockId = ''): ?string
    {
        if ($html === '') {
            return null;
        }

        $navModifier = SiteNavBlocks::isNavBlockId($blockId)
            ? ' voodbuilder-gjs-block-preview--nav'
            : '';

        return '<div class="voodbuilder-gjs-block-preview voodbuilder-gjs-block-preview--site'.$navModifier.'"><div class="voodbuilder-gjs-block-preview__scale">'.$html.'</div></div>';
    }
}
