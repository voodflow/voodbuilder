<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Services\VoodbuilderPortalClient;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Compact licence/edition payload for the editor boot splash and topbar.
 *
 * Same source as entitlements (AnyStack / api.voodflow.com) — never invents an edition.
 *
 * @phpstan-type Summary array{
 *     slug: string,
 *     label: string,
 *     badge: string,
 *     active: bool,
 *     expires_at: ?string,
 *     message: ?string,
 *     package_version: string,
 *     package_latest: ?string,
 *     package_status: 'current'|'update'|'ahead'|'unknown',
 *     package_status_label: string,
 *     licence_configured: bool,
 *     docs_url: string,
 * }
 */
final class EditorEditionSummary
{
    /**
     * @return Summary
     */
    public static function make(?EntitlementManager $manager = null): array
    {
        $manager ??= Voodbuilder::entitlements();
        $status = $manager->licenceStatus();
        $slug = EditionCapabilityMatrix::marketingSlug($status->edition);
        $installed = VoodbuilderPackageVersion::current();
        $package = self::resolvePackageStatus($installed);

        return [
            'slug' => $slug,
            'label' => EditionCapabilityMatrix::marketingLabel($status->edition),
            'badge' => (string) __('voodbuilder::pro.editor_ui.edition_badge_'.$slug),
            'active' => $status->active,
            'expires_at' => $status->expiresAt,
            'message' => $status->message,
            'package_version' => $installed,
            'package_latest' => $package['latest'],
            'package_status' => $package['status'],
            'package_status_label' => $package['label'],
            'licence_configured' => LicenceKeyResolver::resolve() !== '',
            'docs_url' => (string) config('voodbuilder.docs_url', 'https://docs.voodflow.com'),
        ];
    }

    /**
     * @return array{status: 'current'|'update'|'ahead'|'unknown', latest: ?string, label: string}
     */
    private static function resolvePackageStatus(string $installed): array
    {
        $latest = null;

        try {
            $latest = app(VoodbuilderPortalClient::class)->getLatestPublishedTag();
        } catch (\Throwable) {
            $latest = null;
        }

        if ($latest === null || trim($latest) === '') {
            return [
                'status' => 'unknown',
                'latest' => null,
                'label' => $installed,
            ];
        }

        $localNorm = VoodbuilderPackageVersion::normalize($installed);
        $remoteNorm = VoodbuilderPackageVersion::normalize($latest);
        $latestDisplay = ltrim(trim($latest), 'vV');

        if (version_compare($localNorm, $remoteNorm, '>')) {
            return [
                'status' => 'ahead',
                'latest' => $latestDisplay,
                'label' => (string) __('voodbuilder::pro.editor_ui.edition_info_package_ahead', [
                    'tag' => $latestDisplay,
                ]),
            ];
        }

        if (version_compare($localNorm, $remoteNorm, '<')) {
            return [
                'status' => 'update',
                'latest' => $latestDisplay,
                'label' => (string) __('voodbuilder::pro.editor_ui.edition_info_package_update', [
                    'tag' => $latestDisplay,
                ]),
            ];
        }

        return [
            'status' => 'current',
            'latest' => $latestDisplay,
            'label' => (string) __('voodbuilder::pro.editor_ui.edition_info_package_current'),
        ];
    }
}
