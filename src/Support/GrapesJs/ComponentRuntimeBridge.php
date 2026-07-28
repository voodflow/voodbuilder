<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;

/**
 * Optional hooks into voodflow/voodbuilder-components when the companion plugin is installed.
 *
 * Core public rendering keeps working without the plugin; component CSS/HTML expansion is skipped.
 */
final class ComponentRuntimeBridge
{
    public static function moduleEnabled(): bool
    {
        if (! class_exists(ComponentsModule::class)) {
            return false;
        }

        return ComponentsModule::isEnabled();
    }

    public static function globalClassCss(): ?string
    {
        if (! class_exists(GrapesJsGlobalClassRenderer::class)) {
            return null;
        }

        $css = app(GrapesJsGlobalClassRenderer::class)->css();

        return filled($css) ? $css : null;
    }

    public static function componentCssForHtml(string $html): ?string
    {
        if (! class_exists(GrapesJsComponentCssRenderer::class)) {
            return null;
        }

        $css = app(GrapesJsComponentCssRenderer::class)->cssForHtml($html);

        return filled($css) ? $css : null;
    }

    public static function renderComponentHtml(string $html, ?SitePage $page): string
    {
        if (! class_exists(GrapesJsComponentRenderer::class)) {
            return $html;
        }

        return app(GrapesJsComponentRenderer::class)->render($html, $page);
    }

    public static function syncComponentCssLibraryFromPageHtml(string $html): void
    {
        if (! class_exists(GrapesJsComponentCssLibrarySync::class)) {
            return;
        }

        app(GrapesJsComponentCssLibrarySync::class)->syncFromPageHtml($html);
    }
}
