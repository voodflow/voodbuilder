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
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_AGENCY),
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

    public function test_agency_summary_lists_edition_companions_and_extras(): void
    {
        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response(['tag' => '0.0.1'], 200),
            'https://repo.packagist.org/p2/*' => Http::response(['packages' => []], 200),
        ]);

        $summary = EditorEditionSummary::make();
        $byGroup = collect($summary['packages'])->groupBy('group');

        $this->assertSame('Agency edition', $summary['edition_packages_title']);
        $this->assertTrue($byGroup->has('core'));
        $this->assertTrue($byGroup->has('edition'));
        $this->assertTrue($byGroup->has('extra'));

        $core = $byGroup->get('core')->firstWhere('id', 'voodbuilder');
        $this->assertIsArray($core);
        $this->assertSame('packagist', $core['channel']);
        $this->assertArrayHasKey('registered', $core);
        $this->assertArrayHasKey('active', $core);

        $editionIds = $byGroup->get('edition')->pluck('id')->all();
        $this->assertSame([
            'voodbuilder-elements',
            'voodbuilder-components',
            'voodbuilder-templates',
            'voodbuilder-dynamic-data',
            'voodbuilder-dynamic-api',
            'vpopups',
        ], $editionIds);

        $extraIds = $byGroup->get('extra')->pluck('id')->all();
        $this->assertSame(['vmedia', 'vcookiebar'], $extraIds);

        $this->assertFalse(
            collect($summary['packages'])->contains(fn (array $row): bool => in_array($row['id'], ['vdocs', 'vtuts', 'voodflow'], true)),
        );
    }

    public function test_community_summary_omits_edition_companions(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        Http::fake([
            VoodbuilderApiEndpoints::latestVersionUrl() => Http::response(['tag' => '0.0.1'], 200),
            'https://repo.packagist.org/p2/*' => Http::response(['packages' => []], 200),
        ]);

        $summary = EditorEditionSummary::make();
        $groups = collect($summary['packages'])->pluck('group')->unique()->values()->all();

        $this->assertNull($summary['edition_packages_title']);
        $this->assertSame(['core', 'extra'], $groups);
    }

    public function test_packagist_client_picks_highest_stable_and_refreshes_stale_ahead_cache(): void
    {
        $client = app(RemotePackageVersionClient::class);
        $client->forget('packagist', 'voodflow/voodbuilder');

        Cache::put(
            RemotePackageVersionClient::CACHE_PREFIX.'packagist.voodflow.voodbuilder',
            '0.1.87',
            now()->addDay(),
        );

        Http::fake([
            'https://repo.packagist.org/p2/voodflow/voodbuilder.json' => Http::response([
                'packages' => [
                    'voodflow/voodbuilder' => [
                        ['version' => '0.1.87'],
                        ['version' => '0.1.89'],
                        ['version' => 'dev-main'],
                        ['version' => '0.1.88'],
                    ],
                ],
            ], 200),
        ]);

        $latest = $client->latest('packagist', 'voodflow/voodbuilder', '0.1.89');

        $this->assertSame('0.1.89', $latest);
    }
}
