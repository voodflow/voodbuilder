<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\DynamicData\DynamicDataModule;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorRepeatRenderer;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Optional List-repeat hooks into voodflow/voodbuilder-dynamic-data.
 *
 * Two questions that look alike and are not. "May this installation *build* a List
 * repeat?" is commercial: it needs the Filament plugin and the
 * dynamic-data.collections entitlement. "Must this published page still show the list
 * it already contains?" is not, and it must never consult the entitlement — see
 * {@see renderingEnabled()}.
 */
final class DynamicDataCollectionsBridge
{
    /**
     * May the author create and configure List repeats?
     *
     * Drives the editor: the repeat source catalog, the bindings panel, live preview.
     */
    public static function authoringEnabled(): bool
    {
        return self::renderingEnabled() && Voodbuilder::can('dynamic-data.collections');
    }

    /**
     * Can a repeat that is already stored in a page be expanded?
     *
     * Deliberately blind to entitlements. A repeat is not a feature the reader is using,
     * it is the author's content: the section holding the list was written while the
     * licence was valid, and the visitor is looking at a page that has been published.
     * Consulting the entitlement here would mean a lapsed renewal — or an outage of our
     * own licensing endpoint outliving the grace window — silently empties product grids
     * and article lists on live customer sites. Billing state is allowed to stop authoring;
     * it is not allowed to unpublish content that is already public.
     *
     * The plugin check stays: without the companion package there is no renderer to call.
     */
    public static function renderingEnabled(): bool
    {
        if (! self::safeClassExists(DynamicDataModule::class)) {
            return false;
        }

        return DynamicDataModule::isEnabled();
    }

    public static function renderRepeats(string $html, ?SitePage $page = null): string
    {
        if (! self::renderingEnabled() || ! self::safeClassExists(EditorRepeatRenderer::class)) {
            return $html;
        }

        try {
            return app(EditorRepeatRenderer::class)->render($html, $page);
        } catch (\Throwable) {
            return $html;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function repeatSourcesCatalog(): array
    {
        if (! self::authoringEnabled()) {
            return [];
        }

        try {
            $integrationCatalog = app(ModelIntegrationRegistry::class)->repeatCatalog();
            $packageCatalog = self::safeClassExists(RepeatListRegistry::class)
                ? app(RepeatListRegistry::class)->catalog()
                : [];

            return array_values(array_merge($integrationCatalog, $packageCatalog));
        } catch (\Throwable) {
            return [];
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
