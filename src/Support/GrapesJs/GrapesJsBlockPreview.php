<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsBlockPreview
{
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
