<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Filament\RichContent\Landing\LandingFooterBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingNavbarBlock;
use Voodflow\Vpress\Support\LandingFooterSupport;
use Voodflow\Vpress\Support\LandingNavbarSupport;
use Voodflow\Vpress\Support\VpressLandingBlocks;
use Voodflow\Vpress\Vpress;

final class VpressLandingGrapesJsBlocks
{
    public static function register(): void
    {
        $category = 'Vpress / Landing';

        foreach (VpressLandingBlocks::blockClasses() as $blockClass) {
            Vpress::grapesJsRichContentBlock($category, $blockClass);
        }

        self::registerTailblocksFooterVariants();
        self::registerTailblocksNavbarVariants();
    }

    protected static function registerTailblocksFooterVariants(): void
    {
        $blockClass = LandingFooterBlock::class;
        $blockId = $blockClass::getId();

        foreach (LandingFooterSupport::tailblocksVariantOptions() as $variant => $label) {
            $config = array_merge(
                GrapesJsDefaultBlockConfig::for($blockClass),
                ['variant' => $variant],
            );

            $inner = GrapesJsRichContentBlockAdapter::editorPreviewHtml($blockClass, $config);

            Vpress::grapesJsBlock(
                "vpress-{$blockId}-{$variant}",
                $label,
                'Vpress / Landing Footer',
                GrapesJsRichContentBlockAdapter::wrap($blockId, $config, $inner),
            );
        }
    }

    protected static function registerTailblocksNavbarVariants(): void
    {
        $blockClass = LandingNavbarBlock::class;
        $blockId = $blockClass::getId();

        foreach (LandingNavbarSupport::variantOptions() as $variant => $label) {
            $config = array_merge(
                GrapesJsDefaultBlockConfig::for($blockClass),
                ['variant' => $variant],
            );

            $inner = GrapesJsRichContentBlockAdapter::editorPreviewHtml($blockClass, $config);

            Vpress::grapesJsBlock(
                "vpress-{$blockId}-{$variant}",
                $label,
                'Vpress / Landing Navbar',
                GrapesJsRichContentBlockAdapter::wrap($blockId, $config, $inner),
            );
        }
    }
}
