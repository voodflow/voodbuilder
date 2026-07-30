<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * Editor Host Chrome.
 */
final class EditorHostChrome
{
    public static function shouldSuppressHostRender(?bool $editorEditor = null): bool
    {
        if ($editorEditor === true) {
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
body.voodbuilder-editor-editing [data-voodbuilder-chrome-shell] {
    display: none !important;
}
body.voodbuilder-editor-editing .voodbuilder-editor-boot-overlay:not([hidden]) {
    position: fixed;
    inset: 0;
    z-index: 2147483646;
}
CSS;
    }
}
