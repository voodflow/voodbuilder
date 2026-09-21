<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Services\RemotePackageVersionClient;
use Voodflow\Voodbuilder\Support\ComposerPackageVersion;
use Voodflow\Voodbuilder\Support\Editor\ComponentRuntimeBridge;
use Voodflow\Voodbuilder\Support\Editor\EditorCommunityBlockCatalog;
use Voodflow\Voodbuilder\Support\Editor\TemplateAuthoringBridge;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Voodbuilder;
use Voodflow\Voodbuilder\VoodbuilderServiceProvider;

/**
 * Package inventory for the edition modal.
 *
 * Groups:
 * - core: VoodBuilder (Packagist)
 * - edition: companions bundled with Agency / Developer (always listed for that edition)
 * - extra: Vmedia + Vcookiebar (free Packagist companions)
 *
 * @phpstan-type PackageRow array{
 *     id: string,
 *     name: string,
 *     composer: string,
 *     group: 'core'|'edition'|'extra',
 *     channel: 'portal'|'packagist'|'none',
 *     channel_label: string,
 *     installed: bool,
 *     registered: bool|null,
 *     active: bool|null,
 *     version: ?string,
 *     latest: ?string,
 *     status: 'current'|'update'|'ahead'|'unknown'|'missing',
 *     status_label: string,
 * }
 */
final class EditorPackageInventory
{
    /**
     * Companion package ids included in each paid edition bundle.
     *
     * @see resources/js/editor/plugin-bridge.js discoverCompanionPlugins()
     *
     * @var array<string, list<string>>
     */
    private const EDITION_COMPANIONS = [
        'developer' => [
            'voodbuilder-elements',
            'voodbuilder-templates',
            'voodbuilder-dynamic-data',
        ],
        'agency' => [
            'voodbuilder-elements',
            'voodbuilder-components',
            'voodbuilder-templates',
            'voodbuilder-dynamic-data',
            'voodbuilder-dynamic-api',
            'vpopups',
        ],
    ];

    /**
     * @return list<PackageRow>
     */
    public static function make(?EntitlementManager $manager = null): array
    {
        $manager ??= Voodbuilder::entitlements();
        $remote = app(RemotePackageVersionClient::class);
        $slug = EditionCapabilityMatrix::marketingSlug($manager->licenceStatus()->edition);
        $allowedEditionIds = self::EDITION_COMPANIONS[$slug] ?? [];
        $rows = [];

        foreach (self::catalog() as $definition) {
            $group = (string) $definition['group'];
            $id = (string) $definition['id'];

            if ($group === 'edition') {
                if ($allowedEditionIds === [] || ! in_array($id, $allowedEditionIds, true)) {
                    continue;
                }
            }

            $row = self::resolveRow($definition, $remote);

            // Core, edition companions, and free extras are always listed (missing rows show as not installed).
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function catalog(): array
    {
        return [
            [
                'id' => 'voodbuilder',
                'name' => 'VoodBuilder',
                'composer' => 'voodflow/voodbuilder',
                'group' => 'core',
                'channel' => 'packagist',
                'fallback_class' => VoodbuilderServiceProvider::class,
                'active' => true,
                'registered' => true,
            ],
            [
                'id' => 'voodbuilder-elements',
                'name' => 'VoodBuilder Elements',
                'composer' => 'voodflow/voodbuilder-elements',
                'group' => 'edition',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderElements\\VoodbuilderElementsServiceProvider',
                'facade' => 'Voodflow\\VoodbuilderElements\\VoodbuilderElements',
                'active_resolver' => static fn (): ?bool => EditorCommunityBlockCatalog::elementsLibraryActive(),
            ],
            [
                'id' => 'voodbuilder-components',
                'name' => 'VoodBuilder Components',
                'composer' => 'voodflow/voodbuilder-components',
                'group' => 'edition',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderComponents\\VoodbuilderComponentsServiceProvider',
                'facade' => 'Voodflow\\VoodbuilderComponents\\VoodbuilderComponents',
                'active_resolver' => static fn (): ?bool => ComponentRuntimeBridge::moduleEnabled(),
            ],
            [
                'id' => 'voodbuilder-templates',
                'name' => 'VoodBuilder Templates',
                'composer' => 'voodflow/voodbuilder-templates',
                'group' => 'edition',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderTemplates\\VoodbuilderTemplatesServiceProvider',
                'facade' => 'Voodflow\\VoodbuilderTemplates\\VoodbuilderTemplates',
                'active_resolver' => static fn (): ?bool => TemplateAuthoringBridge::pluginInstalled() && TemplatesModule::isEnabled(),
            ],
            [
                'id' => 'voodbuilder-dynamic-data',
                'name' => 'VoodBuilder Dynamic Data',
                'composer' => 'voodflow/voodbuilder-dynamic-data',
                'group' => 'edition',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderDynamicData\\VoodbuilderDynamicDataServiceProvider',
                'facade' => 'Voodflow\\VoodbuilderDynamicData\\VoodbuilderDynamicData',
                'active_resolver' => static fn (): ?bool => self::companionEnabled('Voodflow\\VoodbuilderDynamicData\\VoodbuilderDynamicData'),
            ],
            [
                'id' => 'voodbuilder-dynamic-api',
                'name' => 'VoodBuilder Dynamic API',
                'composer' => 'voodflow/voodbuilder-dynamic-api',
                'group' => 'edition',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderDynamicApi\\VoodbuilderDynamicApiServiceProvider',
                'facade' => 'Voodflow\\VoodbuilderDynamicApi\\VoodbuilderDynamicApi',
                'active_resolver' => static fn (): ?bool => self::companionEnabled('Voodflow\\VoodbuilderDynamicApi\\VoodbuilderDynamicApi'),
            ],
            [
                'id' => 'vpopups',
                'name' => 'VoodPopups',
                'composer' => 'voodflow/vpopups',
                'group' => 'edition',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\Vpopups\\VpopupsServiceProvider',
                'facade' => 'Voodflow\\Vpopups\\Vpopups',
                'active_resolver' => static fn (): ?bool => self::companionEnabled('Voodflow\\Vpopups\\Vpopups')
                    ?? (Voodbuilder::modules()->isEnabled('popups') ? true : null),
            ],
            [
                'id' => 'vmedia',
                'name' => 'Vmedia',
                'composer' => 'voodflow/vmedia',
                'group' => 'extra',
                'channel' => 'packagist',
                'fallback_class' => 'Voodflow\\Vmedia\\VmediaServiceProvider',
                'facade' => 'Voodflow\\Vmedia\\Vmedia',
                'active_resolver' => static fn (): ?bool => self::companionEnabled('Voodflow\\Vmedia\\Vmedia'),
            ],
            [
                'id' => 'vcookiebar',
                'name' => 'Vcookiebar',
                'composer' => 'voodflow/vcookiebar',
                'group' => 'extra',
                'channel' => 'packagist',
                'fallback_class' => 'Voodflow\\Vcookiebar\\VcookiebarServiceProvider',
                'facade' => 'Voodflow\\Vcookiebar\\Vcookiebar',
                'active_resolver' => static fn (): ?bool => self::companionEnabled('Voodflow\\Vcookiebar\\Vcookiebar'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return PackageRow
     */
    private static function resolveRow(array $definition, RemotePackageVersionClient $remote): array
    {
        $composer = (string) $definition['composer'];
        $fallback = isset($definition['fallback_class']) && is_string($definition['fallback_class'])
            ? $definition['fallback_class']
            : null;
        $facade = isset($definition['facade']) && is_string($definition['facade'])
            ? $definition['facade']
            : null;
        $installed = $composer === 'voodflow/voodbuilder'
            || ComposerPackageVersion::isInstalled($composer)
            || ($fallback !== null && self::safeClassExists($fallback))
            || ($facade !== null && self::safeClassExists($facade));
        $version = $installed
            ? (
                $composer === 'voodflow/voodbuilder'
                    ? VoodbuilderPackageVersion::current()
                    : ComposerPackageVersion::current($composer, $fallback)
            )
            : null;

        $channel = (string) ($definition['channel'] ?? 'none');
        $latest = null;

        if ($installed && $channel === 'portal') {
            $latest = $remote->latest(
                'portal',
                (string) ($definition['portal_slug'] ?? $definition['id']),
                $version,
            );
        } elseif ($installed && $channel === 'packagist') {
            $latest = $remote->latest('packagist', $composer, $version);
        }

        $registered = null;
        $active = null;

        if (array_key_exists('registered', $definition)) {
            $registered = (bool) $definition['registered'];
        } elseif ($installed) {
            $registered = self::companionRegistered($facade);
        } else {
            $registered = false;
        }

        if (array_key_exists('active', $definition)) {
            $active = (bool) $definition['active'];
        } elseif (! $installed) {
            $active = false;
        } elseif (isset($definition['active_resolver']) && is_callable($definition['active_resolver'])) {
            $resolved = ($definition['active_resolver'])();
            $active = is_bool($resolved) ? $resolved : null;
        }

        [$status, $statusLabel] = self::statusPair(
            installed: $installed,
            version: $version,
            latest: $latest,
            channel: $channel,
        );

        return [
            'id' => (string) $definition['id'],
            'name' => (string) $definition['name'],
            'composer' => $composer,
            'group' => (string) $definition['group'],
            'channel' => $channel,
            'channel_label' => self::channelLabel($channel),
            'installed' => $installed,
            'registered' => $registered,
            'active' => $active,
            'version' => $version,
            'latest' => $latest,
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }

    /**
     * @return array{0: 'current'|'update'|'ahead'|'unknown'|'missing', 1: string}
     */
    private static function statusPair(bool $installed, ?string $version, ?string $latest, string $channel): array
    {
        if (! $installed) {
            return [
                'missing',
                (string) __('voodbuilder::pro.editor_ui.edition_info_pkg_missing'),
            ];
        }

        if ($latest === null || trim($latest) === '' || $channel === 'none') {
            return [
                'unknown',
                $version !== null && $version !== ''
                    ? (string) __('voodbuilder::pro.editor_ui.edition_info_pkg_local_only', ['version' => $version])
                    : (string) __('voodbuilder::pro.editor_ui.edition_info_pkg_unknown'),
            ];
        }

        $localNorm = VoodbuilderPackageVersion::normalize((string) $version);
        $remoteNorm = VoodbuilderPackageVersion::normalize($latest);

        if (version_compare($localNorm, $remoteNorm, '>')) {
            return [
                'ahead',
                (string) __('voodbuilder::pro.editor_ui.edition_info_pkg_ahead_short', [
                    'tag' => $remoteNorm,
                ]),
            ];
        }

        if (version_compare($localNorm, $remoteNorm, '<')) {
            return [
                'update',
                (string) __('voodbuilder::pro.editor_ui.edition_info_pkg_update_short', [
                    'tag' => $remoteNorm,
                ]),
            ];
        }

        return [
            'current',
            (string) __('voodbuilder::pro.editor_ui.edition_info_package_current'),
        ];
    }

    private static function channelLabel(string $channel): string
    {
        return match ($channel) {
            'packagist' => (string) __('voodbuilder::pro.editor_ui.edition_info_source_packagist'),
            'portal' => (string) __('voodbuilder::pro.editor_ui.edition_info_source_anystack'),
            default => (string) __('voodbuilder::pro.editor_ui.edition_info_source_local'),
        };
    }

    /**
     * Filament panel registration (`Plugin::register` → `activate()`).
     *
     * @param  class-string|null  $facade
     */
    private static function companionRegistered(?string $facade): ?bool
    {
        if ($facade === null || ! self::safeClassExists($facade)) {
            return null;
        }

        try {
            if (method_exists($facade, 'isActivated')) {
                return (bool) $facade::isActivated();
            }
        } catch (\Throwable) {
            return null;
        }

        return true;
    }

    /**
     * Runtime enablement (config / module), independent of panel registration.
     *
     * @param  class-string  $class
     */
    private static function companionEnabled(string $class): ?bool
    {
        if (! self::safeClassExists($class)) {
            return null;
        }

        try {
            if (method_exists($class, 'isEnabled')) {
                return (bool) $class::isEnabled();
            }

            if (method_exists($class, 'isActivated')) {
                return (bool) $class::isActivated();
            }
        } catch (\Throwable) {
            return true;
        }

        return true;
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
