<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingFooterBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingNavbarBlock;
use Voodflow\Voodbuilder\Support\LandingFooterSupport;
use Voodflow\Voodbuilder\Support\LandingNavbarSupport;
use Voodflow\Voodbuilder\Support\VoodbuilderLandingBlocks;
use Voodflow\Voodbuilder\Voodbuilder;

final class VoodbuilderLandingGrapesJsBlocks
{
    public static function register(): void
    {
        $category = 'Voodbuilder / Landing';

        foreach (VoodbuilderLandingBlocks::blockClasses() as $blockClass) {
            Voodbuilder::grapesJsRichContentBlock($category, $blockClass);
        }

        self::registerFooterLayoutVariants();
        self::registerNavbarLayoutVariants();
    }

    protected static function registerFooterLayoutVariants(): void
    {
        $blockClass = LandingFooterBlock::class;
        $blockId = $blockClass::getId();

        foreach (LandingFooterSupport::layoutVariantOptions() as $variant => $label) {
            $config = array_merge(
                GrapesJsDefaultBlockConfig::for($blockClass),
                ['variant' => $variant],
            );

            $inner = GrapesJsRichContentBlockAdapter::editorPreviewHtml($blockClass, $config);

            Voodbuilder::grapesJsBlock(
                "voodbuilder-{$blockId}-{$variant}",
                $label,
                'Voodbuilder / Landing Footer',
                GrapesJsRichContentBlockAdapter::wrap($blockId, $config, $inner),
            );
        }
    }

    protected static function registerNavbarLayoutVariants(): void
    {
        $blockClass = LandingNavbarBlock::class;
        $blockId = $blockClass::getId();

        foreach (LandingNavbarSupport::variantOptions() as $variant => $label) {
            $config = array_merge(
                GrapesJsDefaultBlockConfig::for($blockClass),
                ['variant' => $variant],
            );

            $inner = GrapesJsRichContentBlockAdapter::editorPreviewHtml($blockClass, $config);

            Voodbuilder::grapesJsBlock(
                "voodbuilder-{$blockId}-{$variant}",
                $label,
                'Voodbuilder / Landing Navbar',
                GrapesJsRichContentBlockAdapter::wrap($blockId, $config, $inner),
            );
        }
    }
}
