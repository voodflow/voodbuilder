<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockPreview;

final class ChromeLayoutDefaults
{
    public static function contentSlotHtml(): string
    {
        return <<<'HTML'
<div
    data-voodbuilder-content-slot="main"
    data-placeholder="Plugin content loads in this area (docs, tutorials, blog, pages…)."
    data-gjs-type="voodbuilder-chrome-content-slot"
    class="voodbuilder-chrome-content-slot flex min-h-[12rem] flex-1 flex-col items-center justify-center border border-dashed border-vp-divider bg-vp-bg-alt/40 px-6 py-10 text-center text-sm text-vp-text-3"
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

        return GrapesJsBlockPreview::wrapHtml($inner) ?? $inner;
    }

    public static function navZoneHtml(): string
    {
        return <<<'HTML'
<div
    data-voodbuilder-chrome-drop-zone="nav"
    data-placeholder="Drop header blocks here"
    data-gjs-type="voodbuilder-chrome-drop-zone"
    class="voodbuilder-chrome-drop-zone voodbuilder-chrome-drop-zone--nav"
></div>
HTML;
    }

    public static function footerZoneHtml(): string
    {
        return <<<'HTML'
<div
    data-voodbuilder-chrome-drop-zone="footer"
    data-placeholder="Drop footer blocks here"
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
