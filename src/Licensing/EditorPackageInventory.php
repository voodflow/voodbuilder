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
 * Installed Voodflow packages + local/remote version status for the edition modal.
 *
 * @phpstan-type PackageRow array{
 *     id: string,
 *     name: string,
 *     composer: string,
 *     group: 'core'|'packagist'|'anystack_bundle'|'anystack_companion',
 *     channel: 'portal'|'packagist'|'none',
 *     channel_label: string,
 *     installed: bool,
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
     * @return list<PackageRow>
     */
    public static function make(?EntitlementManager $manager = null): array
    {
        $manager ??= Voodbuilder::entitlements();
        $remote = app(RemotePackageVersionClient::class);
        $rows = [];

        foreach (self::catalog() as $definition) {
            $row = self::resolveRow($definition, $manager, $remote);

            // Always include core; companions / bundles only when installed.
            if ($row['group'] === 'core' || $row['installed']) {
                $rows[] = $row;
            }
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
                'channel' => 'portal',
                'portal_slug' => 'voodbuilder',
                'fallback_class' => VoodbuilderServiceProvider::class,
                'active' => true,
            ],
            [
                'id' => 'vmedia',
                'name' => 'Vmedia',
                'composer' => 'voodflow/vmedia',
                'group' => 'packagist',
                'channel' => 'packagist',
                'fallback_class' => 'Voodflow\\Vmedia\\VmediaServiceProvider',
                'active_resolver' => static fn (): ?bool => self::safeClassExists('Voodflow\\Vmedia\\Vmedia') ? true : null,
            ],
            [
                'id' => 'vcookiebar',
                'name' => 'Vcookiebar',
                'composer' => 'voodflow/vcookiebar',
                'group' => 'packagist',
                'channel' => 'packagist',
                'fallback_class' => 'Voodflow\\Vcookiebar\\VcookiebarServiceProvider',
                'active_resolver' => static fn (): ?bool => self::safeClassExists('Voodflow\\Vcookiebar\\Vcookiebar') ? true : null,
            ],
            [
                'id' => 'voodflow',
                'name' => 'Voodflow',
                'composer' => 'voodflow/voodflow',
                'group' => 'packagist',
                'channel' => 'portal',
                'portal_slug' => 'voodflow',
                'fallback_class' => 'Voodflow\\Voodflow\\VoodflowServiceProvider',
                'active_resolver' => static fn (): ?bool => self::safeClassExists('Voodflow\\Voodflow\\Voodflow') ? true : null,
            ],
            [
                'id' => 'voodbuilder-agency',
                'name' => 'VoodBuilder Agency',
                'composer' => 'voodflow/voodbuilder-agency',
                'group' => 'anystack_bundle',
                'channel' => 'portal',
                'portal_slug' => 'voodbuilder-agency',
                'active_resolver' => static fn (): ?bool => ComposerPackageVersion::isInstalled('voodflow/voodbuilder-agency') ? true : null,
            ],
            [
                'id' => 'voodbuilder-developer',
                'name' => 'VoodBuilder Developer',
                'composer' => 'voodflow/voodbuilder-developer',
                'group' => 'anystack_bundle',
                'channel' => 'portal',
                'portal_slug' => 'voodbuilder-developer',
                'active_resolver' => static fn (): ?bool => ComposerPackageVersion::isInstalled('voodflow/voodbuilder-developer') ? true : null,
            ],
            [
                'id' => 'voodbuilder-elements',
                'name' => 'Elements',
                'composer' => 'voodflow/voodbuilder-elements',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderElements\\VoodbuilderElementsServiceProvider',
                'active_resolver' => static fn (): ?bool => EditorCommunityBlockCatalog::elementsLibraryActive(),
            ],
            [
                'id' => 'voodbuilder-components',
                'name' => 'Components',
                'composer' => 'voodflow/voodbuilder-components',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderComponents\\VoodbuilderComponentsServiceProvider',
                'active_resolver' => static fn (): ?bool => ComponentRuntimeBridge::moduleEnabled(),
            ],
            [
                'id' => 'voodbuilder-templates',
                'name' => 'Templates',
                'composer' => 'voodflow/voodbuilder-templates',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderTemplates\\VoodbuilderTemplatesServiceProvider',
                'active_resolver' => static fn (): ?bool => TemplateAuthoringBridge::pluginInstalled() && TemplatesModule::isEnabled(),
            ],
            [
                'id' => 'voodbuilder-dynamic-data',
                'name' => 'Dynamic Data',
                'composer' => 'voodflow/voodbuilder-dynamic-data',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderDynamicData\\VoodbuilderDynamicDataServiceProvider',
                'active_resolver' => static fn (): ?bool => self::companionActivated('Voodflow\\VoodbuilderDynamicData\\VoodbuilderDynamicData'),
            ],
            [
                'id' => 'voodbuilder-dynamic-api',
                'name' => 'Dynamic API',
                'composer' => 'voodflow/voodbuilder-dynamic-api',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\VoodbuilderDynamicApi\\VoodbuilderDynamicApiServiceProvider',
                'active_resolver' => static fn (): ?bool => self::companionActivated('Voodflow\\VoodbuilderDynamicApi\\VoodbuilderDynamicApi'),
            ],
            [
                'id' => 'vpopups',
                'name' => 'Popups',
                'composer' => 'voodflow/vpopups',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\Vpopups\\VpopupsServiceProvider',
                'active_resolver' => static fn (): ?bool => self::companionActivated('Voodflow\\Vpopups\\Vpopups')
                    ?? (Voodbuilder::modules()->isEnabled('popups') ? true : null),
            ],
            [
                'id' => 'vdocs',
                'name' => 'Vdocs',
                'composer' => 'voodflow/vdocs',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\Vdocs\\VdocsServiceProvider',
                'active_resolver' => static fn (): ?bool => self::companionActivated('Voodflow\\Vdocs\\Vdocs'),
            ],
            [
                'id' => 'vtuts',
                'name' => 'Vtuts',
                'composer' => 'voodflow/vtuts',
                'group' => 'anystack_companion',
                'channel' => 'none',
                'fallback_class' => 'Voodflow\\Vtuts\\VtutsServiceProvider',
                'active_resolver' => static fn (): ?bool => self::companionActivated('Voodflow\\Vtuts\\Vtuts'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return PackageRow
     */
    private static function resolveRow(array $definition, EntitlementManager $manager, RemotePackageVersionClient $remote): array
    {
        unset($manager); // reserved for future capability overlays

        $composer = (string) $definition['composer'];
        $fallback = isset($definition['fallback_class']) && is_string($definition['fallback_class'])
            ? $definition['fallback_class']
            : null;
        $installed = $composer === 'voodflow/voodbuilder'
            || ComposerPackageVersion::isInstalled($composer)
            || ($fallback !== null && self::safeClassExists($fallback));
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
            $latest = $remote->latest('portal', (string) ($definition['portal_slug'] ?? $definition['id']));
        } elseif ($installed && $channel === 'packagist') {
            $latest = $remote->latest('packagist', $composer);
        }

        $active = null;

        if (array_key_exists('active', $definition)) {
            $active = (bool) $definition['active'];
        } elseif ($installed && isset($definition['active_resolver']) && is_callable($definition['active_resolver'])) {
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
        $source = $channel === 'packagist'
            ? (string) __('voodbuilder::pro.editor_ui.edition_info_source_packagist')
            : (string) __('voodbuilder::pro.editor_ui.edition_info_source_anystack');

        if (version_compare($localNorm, $remoteNorm, '>')) {
            return [
                'ahead',
                (string) __('voodbuilder::pro.editor_ui.edition_info_pkg_ahead', [
                    'source' => $source,
                    'tag' => $remoteNorm,
                ]),
            ];
        }

        if (version_compare($localNorm, $remoteNorm, '<')) {
            return [
                'update',
                (string) __('voodbuilder::pro.editor_ui.edition_info_pkg_update', [
                    'source' => $source,
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
     * @param  class-string  $class
     */
    private static function companionActivated(string $class): ?bool
    {
        if (! self::safeClassExists($class)) {
            return null;
        }

        try {
            if (method_exists($class, 'isActivated') && method_exists($class, 'isEnabled')) {
                return $class::isActivated() && $class::isEnabled();
            }

            if (method_exists($class, 'isEnabled')) {
                return (bool) $class::isEnabled();
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
