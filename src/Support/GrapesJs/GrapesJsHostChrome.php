<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\PageBuilderAccess;

final class GrapesJsHostChrome
{
    public static function shouldSuppressHostRender(?bool $grapesJsEditor = null): bool
    {
        if ($grapesJsEditor === true) {
            return true;
        }

        if (! request()->boolean('edit')) {
            return false;
        }

        return PageBuilderAccess::userCanUsePageBuilder();
    }

    public static function criticalHideCss(): string
    {
        return <<<'CSS'
body.voodbuilder-grapesjs-editing [data-voodbuilder-chrome-shell] {
    display: none !important;
}
body.voodbuilder-grapesjs-editing .voodbuilder-gjs-boot-overlay:not([hidden]) {
    position: fixed;
    inset: 0;
    z-index: 2147483646;
}
CSS;
    }
}
