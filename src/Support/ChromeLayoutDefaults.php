<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Support\Editor\EditorBlockPreview;

final class ChromeLayoutDefaults
{
    public static function contentSlotHtml(): string
    {
        $placeholder = e(__('voodbuilder::chrome_layouts.editor.layout_content_slot_placeholder'));

        return <<<HTML
<div
    data-voodbuilder-content-slot="main"
    data-placeholder="{$placeholder}"
    data-gjs-type="voodbuilder-chrome-content-slot"
    class="voodbuilder-chrome-content-slot"
    aria-hidden="true"
></div>
HTML;
    }

    public static function contentSlotPreviewHtml(): string
    {
        $inner = <<<'HTML'
<div class="flex min-h-[5rem] flex-col items-center justify-center border border-dashed border-vp-divider bg-vp-bg-alt/60 px-3 py-4 text-center text-xs text-vp-text-3">
    <span>Page content</span>
</div>
HTML;

        return EditorBlockPreview::wrapHtml($inner) ?? $inner;
    }

    public static function navZoneHtml(): string
    {
        $placeholder = e(__('voodbuilder::chrome_layouts.editor.layout_nav_zone_placeholder'));

        return <<<HTML
<div
    data-voodbuilder-chrome-drop-zone="nav"
    data-placeholder="{$placeholder}"
    data-gjs-type="voodbuilder-chrome-drop-zone"
    class="voodbuilder-chrome-drop-zone voodbuilder-chrome-drop-zone--nav"
></div>
HTML;
    }

    public static function footerZoneHtml(): string
    {
        $placeholder = e(__('voodbuilder::chrome_layouts.editor.layout_footer_zone_placeholder'));

        return <<<HTML
<div
    data-voodbuilder-chrome-drop-zone="footer"
    data-placeholder="{$placeholder}"
    data-gjs-type="voodbuilder-chrome-drop-zone"
    class="voodbuilder-chrome-drop-zone voodbuilder-chrome-drop-zone--footer"
></div>
HTML;
    }

    public static function starterHtml(): string
    {
        return self::navZoneHtml().self::contentSlotHtml().self::footerZoneHtml();
    }
}
