<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Illuminate\Support\Facades\Cache;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackEntitlementProvider;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Support\Editor\ComponentRuntimeBridge;
use Voodflow\Voodbuilder\Support\Editor\EditorCommunityBlockCatalog;
use Voodflow\Voodbuilder\Support\Editor\TemplateAuthoringBridge;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Versioned JSON payload for a future Filament licenses dashboard plugin.
 *
 * Stable contract: schema_version + products/catalog/cache_grace.
 * Community / Packagist hosts work without an AnyStack key.
 */
final class LicenseDashboardPayload
{
    public const SCHEMA_VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public static function make(?EntitlementManager $manager = null): array
    {
        $manager ??= Voodbuilder::entitlements();
        $status = $manager->licenceStatus();
        $driver = (string) config('voodbuilder.license.driver', 'config');
        $key = trim((string) config('voodbuilder.license.key', ''));
        $capabilities = $manager->capabilities()->all();

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'driver' => $driver,
            'licence' => [
                'configured' => $key !== '',
                'key_masked' => self::maskKey($key),
                'active' => $status->active,
                'edition' => $status->edition,
                // Never echo the raw licence key (config driver often uses it as identifier).
                'identifier' => self::safeIdentifier($status->identifier, $key),
                'expires_at' => $status->expiresAt,
                'message' => $status->message,
                'is_paid_edition' => $status->isPaidEdition(),
            ],
            'capabilities' => [
                'all' => $capabilities,
                'matrix_edition' => $status->edition,
            ],
            'products' => self::products($manager),
            'catalog' => self::catalog(),
            'cache_grace' => self::cacheGrace($driver),
            'distribution' => self::distributionMap(),
        ];
    }

    public static function forgetCachedSnapshots(): void
    {
        Cache::forget(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY);
        Cache::forget('voodbuilder.entitlements.capabilities');
        Cache::forget('voodbuilder.entitlements.status');
    }

    private static function maskKey(string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 4).str_repeat('*', max(4, strlen($key) - 8)).substr($key, -4);
    }

    private static function safeIdentifier(?string $identifier, string $licenceKey): ?string
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        if ($licenceKey !== '' && hash_equals($licenceKey, $identifier)) {
            return self::maskKey($licenceKey);
        }

        if ($licenceKey !== '' && str_contains($identifier, $licenceKey)) {
            return str_replace($licenceKey, (string) self::maskKey($licenceKey), $identifier);
        }

        return $identifier;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function products(EntitlementManager $manager): array
    {
        $elementsInstalled = self::safeClassExists('Voodflow\\VoodbuilderElements\\VoodbuilderElements');
        $elementsActive = EditorCommunityBlockCatalog::elementsLibraryActive();
        $componentsInstalled = ComponentRuntimeBridge::moduleEnabled();
        $templatesInstalled = TemplateAuthoringBridge::pluginInstalled();
        $dynamicInstalled = self::safeClassExists('Voodflow\\VoodbuilderDynamicData\\VoodbuilderDynamicData');
        $vdocsInstalled = self::safeClassExists('Voodflow\\Vdocs\\Vdocs');
        $vtutsInstalled = self::safeClassExists('Voodflow\\Vtuts\\Vtuts');
        $vcookieInstalled = self::safeClassExists('Voodflow\\Vcookiebar\\Vcookiebar');
        $vpopupsInstalled = self::safeClassExists('Voodflow\\Vpopups\\Vpopups');

        return [
            'core' => [
                'slug' => 'voodbuilder',
                'installed' => true,
                'distribution' => 'packagist',
                'edition_capability_ok' => true,
            ],
            'elements' => [
                'slug' => 'voodbuilder-elements',
                'installed' => $elementsInstalled,
                'enabled' => $elementsActive,
                'distribution' => 'anystack',
                'requires_capability' => EditorCommunityBlockCatalog::CAPABILITY_FULL_LIBRARY,
                'capability_granted' => $manager->can(EditorCommunityBlockCatalog::CAPABILITY_FULL_LIBRARY),
                'catalog_ready' => CatalogCredentialResolver::elementsReady(),
            ],
            'components' => [
                'slug' => 'voodbuilder-components',
                'installed' => $componentsInstalled,
                'enabled' => $componentsInstalled,
                'distribution' => 'anystack',
                'requires_capability' => 'components.library',
                'capability_granted' => $manager->can('components.library'),
            ],
            'templates' => [
                'slug' => 'voodbuilder-templates',
                'installed' => $templatesInstalled,
                'module_enabled' => TemplatesModule::isEnabled(),
                'authoring_enabled' => TemplateAuthoringBridge::isEnabled(),
                'distribution' => 'anystack',
                'remote_install' => $manager->can('templates.remote-install'),
                'catalog_ready' => CatalogCredentialResolver::pageTemplatesReady(),
            ],
            'dynamic_data' => [
                'slug' => 'voodbuilder-dynamic-data',
                'installed' => $dynamicInstalled,
                'distribution' => 'anystack',
                'single' => $manager->can('dynamic-data.single'),
                'collections' => $manager->can('dynamic-data.collections'),
            ],
            'popups' => [
                'slug' => 'vpopups',
                'installed' => $vpopupsInstalled,
                'distribution' => 'anystack',
                'module_enabled' => Voodbuilder::modules()->isEnabled('popups'),
                'capability_builder' => $manager->can('popups.builder'),
                'separate_sku' => true,
            ],
            'vdocs' => [
                'slug' => 'vdocs',
                'installed' => $vdocsInstalled,
                'distribution' => 'anystack',
                'licensed_by_voodbuilder' => false,
            ],
            'vtuts' => [
                'slug' => 'vtuts',
                'installed' => $vtutsInstalled,
                'distribution' => 'anystack',
                'licensed_by_voodbuilder' => false,
            ],
            'vmedia' => [
                'slug' => 'vmedia',
                'installed' => self::safeClassExists('Voodflow\\Vmedia\\Vmedia'),
                'distribution' => 'packagist',
                'licensed_by_voodbuilder' => false,
            ],
            'vcookiebar' => [
                'slug' => 'vcookiebar',
                'installed' => $vcookieInstalled,
                'distribution' => 'packagist',
                'licensed_by_voodbuilder' => false,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function catalog(): array
    {
        return [
            'elements' => CatalogCredentialResolver::elementsStatus(),
            'page_templates' => CatalogCredentialResolver::pageTemplatesStatus(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cacheGrace(string $driver): array
    {
        /** @var array<string, mixed>|null $snapshot */
        $snapshot = Cache::get(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY);
        $grace = (int) config('voodbuilder.license.anystack.grace_seconds', 604800);
        $fetchedAt = is_array($snapshot) ? (int) ($snapshot['fetched_at'] ?? 0) : null;
        $age = $fetchedAt !== null && $fetchedAt > 0 ? max(0, time() - $fetchedAt) : null;
        $withinGrace = $age !== null ? $age <= $grace : null;

        $mode = match (true) {
            $driver === 'anystack' && is_array($snapshot) && $withinGrace === true
                && filled($snapshot['message'] ?? null) => 'grace',
            $driver === 'anystack' && is_array($snapshot) => 'live_or_cached',
            $driver === 'anystack' => 'community_fallback_or_empty',
            default => 'config',
        };

        return [
            'provider' => $driver,
            'cache_enabled' => (bool) config('voodbuilder.license.cache', true),
            'cache_ttl_seconds' => (int) config('voodbuilder.license.cache_ttl', 3600),
            'anystack_grace_seconds' => $grace,
            'snapshot_present' => is_array($snapshot),
            'snapshot_fetched_at' => $fetchedAt,
            'snapshot_age_seconds' => $age,
            'within_grace' => $withinGrace,
            'mode' => $mode,
        ];
    }

    /**
     * Commercial distribution map for the future dashboard UI.
     *
     * @return list<array{slug: string, channel: string, status: string, notes: string}>
     */
    private static function distributionMap(): array
    {
        return [
            ['slug' => 'vmedia', 'channel' => 'packagist', 'status' => 'released', 'notes' => 'MIT / public'],
            ['slug' => 'voodbuilder', 'channel' => 'packagist', 'status' => 'ready', 'notes' => 'Community'],
            ['slug' => 'voodbuilder-elements', 'channel' => 'anystack', 'status' => 'almost_ready', 'notes' => 'Pro + catalog CDN'],
            ['slug' => 'voodbuilder-components', 'channel' => 'anystack', 'status' => 'almost_ready', 'notes' => 'Agency'],
            ['slug' => 'voodbuilder-templates', 'channel' => 'anystack', 'status' => 'almost_ready', 'notes' => 'Pro'],
            ['slug' => 'vpopups', 'channel' => 'anystack', 'status' => 'almost_ready', 'notes' => 'Separate SKU'],
            ['slug' => 'vdocs', 'channel' => 'anystack', 'status' => 'released', 'notes' => 'Own licence'],
            ['slug' => 'vtuts', 'channel' => 'anystack', 'status' => 'released', 'notes' => 'Own licence'],
            ['slug' => 'vcookiebar', 'channel' => 'packagist', 'status' => 'planned', 'notes' => 'When ready'],
        ];
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
