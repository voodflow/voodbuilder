<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\DynamicDataCollections\DynamicDataCollectionsModule;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsRepeatRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\RepeatListRegistry;

/**
 * Optional hooks into voodflow/voodbuilder-dynamic-data when the companion plugin is installed.
 *
 * Community single-record bindings keep working without the plugin; List repeat is skipped.
 */
final class DynamicDataCollectionsBridge
{
    public static function moduleEnabled(): bool
    {
        if (! class_exists(DynamicDataCollectionsModule::class)) {
            return false;
        }

        return DynamicDataCollectionsModule::isEnabled();
    }

    public static function renderRepeats(string $html, ?SitePage $page = null): string
    {
        if (! self::moduleEnabled() || ! class_exists(GrapesJsRepeatRenderer::class)) {
            return $html;
        }

        return app(GrapesJsRepeatRenderer::class)->render($html, $page);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function repeatSourcesCatalog(): array
    {
        if (! self::moduleEnabled()) {
            return [];
        }

        $integrationCatalog = class_exists(ModelIntegrationRegistry::class)
            ? app(ModelIntegrationRegistry::class)->repeatCatalog()
            : [];

        $packageCatalog = class_exists(RepeatListRegistry::class)
            ? app(RepeatListRegistry::class)->catalog()
            : [];

        return array_values(array_merge($integrationCatalog, $packageCatalog));
    }
}
