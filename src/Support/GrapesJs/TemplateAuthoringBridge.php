<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * Optional unlock for page-template authoring via voodflow/voodbuilder-templates.
 *
 * Core always keeps list + install-from-URL (marketplace). Save / JSON import /
 * export / multi-select require the Filament companion plugin when the package
 * is installed. If the package is absent, edition entitlements apply (legacy).
 *
 * class_exists() can throw when Composer classmap points at an unreadable path
 * (stale Docker/virtiofs mounts). Catch and treat as absent.
 */
final class TemplateAuthoringBridge
{
    public static function isEnabled(): bool
    {
        if (self::safeClassExists(\Voodflow\VoodbuilderTemplates\VoodbuilderTemplates::class)) {
            return \Voodflow\VoodbuilderTemplates\VoodbuilderTemplates::authoringEnabled();
        }

        // Legacy hosts without the companion package: keep edition matrix.
        return \Voodflow\Voodbuilder\Voodbuilder::can('templates.local');
    }

    public static function canImportJson(): bool
    {
        if (self::safeClassExists(\Voodflow\VoodbuilderTemplates\VoodbuilderTemplates::class)) {
            return self::isEnabled();
        }

        return \Voodflow\Voodbuilder\Voodbuilder::can('templates.import');
    }

    public static function canExport(): bool
    {
        if (self::safeClassExists(\Voodflow\VoodbuilderTemplates\VoodbuilderTemplates::class)) {
            return self::isEnabled();
        }

        return \Voodflow\Voodbuilder\Voodbuilder::can('templates.export');
    }

    public static function authorizeAuthoring(): void
    {
        abort_unless(self::isEnabled(), 403);
    }

    public static function authorizeImportJson(): void
    {
        abort_unless(self::canImportJson(), 403);
    }

    public static function authorizeExport(): void
    {
        abort_unless(self::canExport(), 403);
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
