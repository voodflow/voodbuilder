<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\EditorEditionSummary;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Services\RemotePackageVersionClient;
use Voodflow\Voodbuilder\Services\VoodbuilderPortalClient;
use Voodflow\Voodbuilder\Support\VoodbuilderApiEndpoints;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

final class EditorEditionPackageStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(VoodbuilderPortalClient::LATEST_CACHE_KEY);
        app(RemotePackageVersionClient::class)->forget('portal', 'voodbuilder');
        app(RemotePackageVersionClient::class)->forget('packagist', 'voodflow/voodbuilder');
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_DEVELOPER),
        );
    }

    public function test_package_status_is_current_when_installed_matches_packagist(): void
    {
        $installed = VoodbuilderPackageVersion::current();

        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response(['tag' => '0.0.1'], 200),
            'https://repo.packagist.org/p2/voodflow/voodbuilder.json' => Http::response([
                'packages' => [
                    'voodflow/voodbuilder' => [
                        ['version' => $installed],
                    ],
                ],
            ], 200),
            'https://repo.packagist.org/p2/*' => Http::response(['packages' => []], 200),
        ]);

        $summary = EditorEditionSummary::make();

        $this->assertSame('current', $summary['package_status']);
        $this->assertSame($installed, $summary['package_latest']);
        $this->assertSame('Current', $summary['package_status_label']);
        $this->assertIsArray($summary['packages']);
        $this->assertNotEmpty($summary['packages']);
        $this->assertSame('voodbuilder', $summary['packages'][0]['id']);
        $this->assertSame('packagist', $summary['packages'][0]['channel']);
        $this->assertSame('core', $summary['packages'][0]['group']);
    }

    public function test_package_status_flags_update_when_behind_packagist(): void
    {
        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response(['tag' => '0.0.1'], 200),
            'https://repo.packagist.org/p2/voodflow/voodbuilder.json' => Http::response([
                'packages' => [
                    'voodflow/voodbuilder' => [
                        ['version' => '99.0.0'],
                    ],
                ],
            ], 200),
            'https://repo.packagist.org/p2/*' => Http::response(['packages' => []], 200),
        ]);

        $summary = EditorEditionSummary::make();

        $this->assertSame('update', $summary['package_status']);
        $this->assertSame('99.0.0', $summary['package_latest']);
        $this->assertStringContainsString('99.0.0', $summary['package_status_label']);
    }

    public function test_package_status_falls_back_to_version_when_packagist_offline(): void
    {
        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response('error', 503),
            'https://repo.packagist.org/p2/*' => Http::response('error', 503),
        ]);

        $summary = EditorEditionSummary::make();

        $this->assertSame('unknown', $summary['package_status']);
        $this->assertNull($summary['package_latest']);
        $this->assertSame(VoodbuilderPackageVersion::current(), $summary['package_status_label']);
    }

    public function test_summary_groups_packages_and_includes_edition_row(): void
    {
        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response(['tag' => '0.0.1'], 200),
            'https://repo.packagist.org/p2/*' => Http::response(['packages' => []], 200),
        ]);

        $summary = EditorEditionSummary::make();
        $core = collect($summary['packages'])->firstWhere('id', 'voodbuilder');
        $edition = collect($summary['packages'])->firstWhere('group', 'edition');

        $this->assertIsArray($core);
        $this->assertTrue($core['installed']);
        $this->assertSame('packagist', $core['channel']);
        $this->assertSame('Community', $core['affiliation_label']);
        $this->assertSame(VoodbuilderPackageVersion::current(), $core['version']);

        $this->assertIsArray($edition);
        $this->assertSame('edition', $edition['group']);
    }
}
