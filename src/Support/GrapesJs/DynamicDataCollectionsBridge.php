<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\DynamicData\DynamicDataModule;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsRepeatRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Optional List-repeat hooks into voodflow/voodbuilder-dynamic-data.
 *
 * Requires the Dynamic Data Filament plugin (DynamicDataModule) plus the
 * dynamic-data.collections entitlement for Pro/Agency List repeat.
 */
final class DynamicDataCollectionsBridge
{
    public static function moduleEnabled(): bool
    {
        if (! self::safeClassExists(DynamicDataModule::class)) {
            return false;
        }

        if (! DynamicDataModule::isEnabled()) {
            return false;
        }

        return Voodbuilder::can('dynamic-data.collections');
    }

    public static function renderRepeats(string $html, ?SitePage $page = null): string
    {
        if (! self::moduleEnabled() || ! self::safeClassExists(GrapesJsRepeatRenderer::class)) {
            return $html;
        }

        try {
            return app(GrapesJsRepeatRenderer::class)->render($html, $page);
        } catch (\Throwable) {
            return $html;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function repeatSourcesCatalog(): array
    {
        if (! self::moduleEnabled()) {
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
