<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;

/**
 * Optional hooks into voodflow/voodbuilder-components when the companion plugin is installed.
 *
 * Core public rendering keeps working without the plugin; component CSS/HTML expansion is skipped.
 *
 * class_exists() can throw ErrorException when Composer classmap points at a path that the
 * runtime filesystem cannot open (stale Docker/virtiofs mounts). Catch and treat as absent.
 */
final class ComponentRuntimeBridge
{
    public static function moduleEnabled(): bool
    {
        if (! self::safeClassExists(ComponentsModule::class)) {
            return false;
        }

        return ComponentsModule::isEnabled();
    }

    public static function globalClassCss(): ?string
    {
        if (! self::safeClassExists(GrapesJsGlobalClassRenderer::class)) {
            return null;
        }

        try {
            $css = app(GrapesJsGlobalClassRenderer::class)->css();
        } catch (\Throwable) {
            return null;
        }

        return filled($css) ? $css : null;
    }

    public static function componentCssForHtml(string $html): ?string
    {
        if (! self::safeClassExists(GrapesJsComponentCssRenderer::class)) {
            return null;
        }

        try {
            $css = app(GrapesJsComponentCssRenderer::class)->cssForHtml($html);
        } catch (\Throwable) {
            return null;
        }

        return filled($css) ? $css : null;
    }

    public static function renderComponentHtml(string $html, ?SitePage $page): string
    {
        if (! self::safeClassExists(GrapesJsComponentRenderer::class)) {
            return $html;
        }

        try {
            return app(GrapesJsComponentRenderer::class)->render($html, $page);
        } catch (\Throwable) {
            return $html;
        }
    }

    public static function syncComponentCssLibraryFromPageHtml(string $html): void
    {
        if (! self::safeClassExists(GrapesJsComponentCssLibrarySync::class)) {
            return;
        }

        try {
            app(GrapesJsComponentCssLibrarySync::class)->syncFromPageHtml($html);
        } catch (\Throwable) {
            // Companion package present in classmap but unavailable at runtime.
        }
    }

    /**
     * @param  class-string  $class
     */
    private static function safeClassExists(string $class): bool
    {
        try {
            return class_exists($class);
        } catch (\Throwable) {
            return false;
        }
    }
}
