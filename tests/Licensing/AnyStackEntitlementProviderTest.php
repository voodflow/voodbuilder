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

    public function test_expired_grace_falls_back_to_community_without_breaking(): void
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

        $this->assertTrue(Voodbuilder::can('editor.core'));
        $this->assertTrue(Voodbuilder::cannot('components.library'));
        $this->assertSame('community', Voodbuilder::entitlements()->edition());
    }
}
