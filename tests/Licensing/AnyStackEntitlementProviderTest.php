<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackEntitlementProvider;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackLicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClientException;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\EntitlementProviderFactory;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class AnyStackEntitlementProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY);
    }

    public function test_remote_success_stores_snapshot_and_grants_capabilities(): void
    {
        Http::fake([
            'https://license.test/entitlements' => Http::response([
                'edition' => 'professional',
                'active' => true,
                'capabilities' => EditionCapabilityMatrix::professional(),
                'identifier' => 'seat-1',
            ], 200),
        ]);

        $provider = new AnyStackEntitlementProvider(
            new AnyStackLicenceClient('https://license.test', 2),
            'vb_test_key_123456789012',
        );

        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertTrue(Voodbuilder::can('dynamic-data.collections'));
        $this->assertTrue(Voodbuilder::cannot('components.library'));
        $this->assertSame('professional', Voodbuilder::entitlements()->edition());
        $this->assertNotNull(Cache::get(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY));
    }

    public function test_outage_uses_grace_snapshot_without_throwing(): void
    {
        Cache::forever(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY, [
            'edition' => 'agency',
            'active' => true,
            'capabilities' => EditionCapabilityMatrix::agency(),
            'identifier' => 'seat-grace',
            'expires_at' => null,
            'message' => null,
            'fetched_at' => time() - 60,
        ]);

        $client = new class implements LicenceClient
        {
            public function fetchEntitlements(string $licenceKey): array
            {
                throw LicenceClientException::unreachable('simulated outage');
            }
        };

        $provider = new AnyStackEntitlementProvider($client, 'vb_test_key_123456789012', graceSeconds: 3600);
        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertTrue(Voodbuilder::can('components.library'));
        $this->assertSame('agency', Voodbuilder::entitlements()->edition());
        $this->assertStringContainsString('cached', strtolower((string) Voodbuilder::entitlements()->licenceStatus()->message));
    }

    public function test_stale_outage_keeps_last_known_entitlements_fail_open(): void
    {
        Cache::forever(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY, [
            'edition' => 'agency',
            'active' => true,
            'capabilities' => EditionCapabilityMatrix::agency(),
            'identifier' => 'seat-old',
            'expires_at' => null,
            'message' => null,
            'fetched_at' => time() - 999999,
        ]);

        $client = new class implements LicenceClient
        {
            public function fetchEntitlements(string $licenceKey): array
            {
                throw LicenceClientException::unreachable('simulated outage');
            }
        };

        $provider = new AnyStackEntitlementProvider($client, 'vb_test_key_123456789012', graceSeconds: 10);
        Voodbuilder::entitlements()->useProvider($provider);

        // Outage must not strip Agency authoring after the grace window — only a deliberate
        // inactive reply from AnyStack may downgrade (see test_inactive_licence_downgrades_authoring).
        $this->assertTrue(Voodbuilder::can('components.library'));
        $this->assertSame('agency', Voodbuilder::entitlements()->edition());
        $this->assertStringContainsString('last known', strtolower((string) Voodbuilder::entitlements()->licenceStatus()->message));
    }

    public function test_inactive_licence_downgrades_authoring_to_community(): void
    {
        Http::fake([
            'https://license.test/entitlements' => Http::response([
                'edition' => 'agency',
                'active' => false,
                'capabilities' => EditionCapabilityMatrix::agency(),
                'identifier' => 'seat-expired',
                'message' => 'Subscription ended',
            ], 200),
        ]);

        $provider = new AnyStackEntitlementProvider(
            new AnyStackLicenceClient('https://license.test', 2),
            'vb_test_key_123456789012',
        );

        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertFalse(Voodbuilder::entitlements()->licenceStatus()->active);
        $this->assertSame('community', Voodbuilder::entitlements()->edition());
        $this->assertTrue(Voodbuilder::can('editor.core'));
        $this->assertTrue(Voodbuilder::cannot('components.library'));
        $this->assertTrue(Voodbuilder::cannot('dynamic-data.collections'));
    }

    public function test_developer_edition_alias_maps_to_professional_capabilities(): void
    {
        Http::fake([
            'https://license.test/entitlements' => Http::response([
                'edition' => 'developer',
                'active' => true,
                'identifier' => 'seat-dev',
            ], 200),
        ]);

        $provider = new AnyStackEntitlementProvider(
            new AnyStackLicenceClient('https://license.test', 2),
            'vb_test_key_123456789012',
        );

        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertSame('professional', Voodbuilder::entitlements()->edition());
        $this->assertTrue(Voodbuilder::can('dynamic-data.collections'));
        $this->assertTrue(Voodbuilder::cannot('components.library'));
        $this->assertTrue(Voodbuilder::cannot('dynamic-api.sources'));
    }

    public function test_outage_without_snapshot_falls_back_to_community(): void
    {
        $client = new class implements LicenceClient
        {
            public function fetchEntitlements(string $licenceKey): array
            {
                throw LicenceClientException::unreachable('simulated outage');
            }
        };

        $provider = new AnyStackEntitlementProvider($client, 'vb_test_key_123456789012', graceSeconds: 10);
        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertSame('community', Voodbuilder::entitlements()->edition());
        $this->assertTrue(Voodbuilder::cannot('components.library'));
    }

    public function test_active_community_reply_does_not_clobber_cached_agency(): void
    {
        Cache::forever(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY, [
            'edition' => 'agency',
            'active' => true,
            'capabilities' => EditionCapabilityMatrix::agency(),
            'identifier' => 'seat-agency',
            'expires_at' => null,
            'message' => null,
            'fetched_at' => time() - 30,
        ]);

        Http::fake([
            'https://license.test/entitlements' => Http::response([
                'edition' => 'community',
                'active' => true,
                'capabilities' => EditionCapabilityMatrix::community(),
                'identifier' => 'weird',
            ], 200),
        ]);

        $provider = new AnyStackEntitlementProvider(
            new AnyStackLicenceClient('https://license.test', 2),
            'vb_test_key_123456789012',
        );
        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertSame('agency', Voodbuilder::entitlements()->edition());
        $this->assertTrue(Voodbuilder::can('components.library'));
        $this->assertStringContainsString('cached', strtolower((string) Voodbuilder::entitlements()->licenceStatus()->message));
    }

    public function test_missing_licence_key_fail_opens_on_cached_snapshot(): void
    {
        Cache::forever(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY, [
            'edition' => 'agency',
            'active' => true,
            'capabilities' => EditionCapabilityMatrix::agency(),
            'identifier' => 'seat-agency',
            'expires_at' => null,
            'message' => null,
            'fetched_at' => time() - 30,
        ]);

        config([
            'voodbuilder.license.driver' => 'anystack',
            'voodbuilder.license.key' => '',
            'voodbuilder.license.cache' => false,
        ]);

        // Force factory path: empty key + snapshot present.
        $this->assertTrue(AnyStackEntitlementProvider::hasCachedSnapshot());

        $provider = EntitlementProviderFactory::make();
        Voodbuilder::entitlements()->useProvider($provider);

        $this->assertInstanceOf(AnyStackEntitlementProvider::class, $provider);
        $this->assertSame('agency', Voodbuilder::entitlements()->edition());
        $this->assertTrue(Voodbuilder::can('components.library'));
    }
}
