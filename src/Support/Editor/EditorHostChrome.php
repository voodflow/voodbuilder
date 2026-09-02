<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * Editor Host Chrome.
 */
final class EditorHostChrome
{
    /**
     * Request attribute announcing that the editor has taken over this response.
     *
     * This is the public, dependency-free contract for companions that inject public-page
     * UI (cookie banners, chat widgets, scroll-to-top…). Such packages do not — and should
     * not have to — depend on voodbuilder to know they must stand down, and `?edit=1` alone
     * is not a safe check: an anonymous visitor can append it, and a cookie banner that
     * disappears for visitors is a compliance problem, not a UX one.
     *
     * Companions read it without importing anything from this package:
     *
     *     if (request()->attributes->getBoolean('voodbuilder.editor_active')) {
     *         return; // the editor owns the viewport
     *     }
     *
     * Injecting anyway is not merely cosmetic: the editor absorbs host page markup into
     * the authoring canvas, so an overlay ends up as editable content on top of the
     * author's footer.
     */
    public const REQUEST_ATTRIBUTE = 'voodbuilder.editor_active';

    /**
     * Announce editor mode for the current request.
     */
    public static function markActive(): void
    {
        request()->attributes->set(self::REQUEST_ATTRIBUTE, true);
    }

    public static function isActive(): bool
    {
        return request()->attributes->getBoolean(self::REQUEST_ATTRIBUTE);
    }

    public static function shouldSuppressHostRender(?bool $editorEditor = null): bool
    {
        if ($editorEditor === true) {
            self::markActive();

            return true;
        }

        if (! request()->boolean('edit')) {
            return false;
        }

        if (! PageBuilderAccess::userCanUsePageBuilder()) {
            return false;
        }

        self::markActive();

        return true;
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
