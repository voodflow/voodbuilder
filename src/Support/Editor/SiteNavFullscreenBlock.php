<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Fullscreen two-column overlay nav (Faselunare-style).
 *
 * Left column ← Admin menu placement "main".
 * Right column ← Admin menu placement "header_extra".
 */
final class SiteNavFullscreenBlock extends AbstractSiteNavVariantBlock
{
    public static function variant(): string
    {
        return 'fullscreen';
    }

    public static function toHtml(array $config, array $context): string
    {
        return self::renderFullscreenShell($config, false);
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return self::renderFullscreenShell($config, true);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function renderFullscreenShell(array $config, bool $preview): string
    {
        $merged = SiteNavConfig::normalize($config);

        return view('voodbuilder::editor.blocks.site-nav-fullscreen', [
            'config' => $merged,
            'preview' => $preview,
            'canvasPreview' => $preview,
        ])->render();
    }
}
