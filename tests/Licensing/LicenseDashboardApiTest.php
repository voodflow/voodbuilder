<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackEntitlementProvider;
use Voodflow\Voodbuilder\Licensing\CatalogCredentialResolver;
use Voodflow\Voodbuilder\Licensing\LicenseDashboardPayload;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

final class LicenseDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY);
        config([
            'voodbuilder.license.driver' => 'config',
            'voodbuilder.license.key' => 'vb_live_abcd1234efgh5678',
            'voodbuilder-elements.catalog_url' => 'https://api.voodflow.com/voodbuilder/elements/catalog.json',
            'voodbuilder-elements.catalog_token' => 'env-catalog-secret',
        ]);
    }

    public function test_status_requires_authentication(): void
    {
        $this->getJson(route('voodbuilder.admin.license.status'))
            ->assertUnauthorized();
    }

    public function test_status_requires_page_builder_access(): void
    {
        $user = $this->makeUser('denied@example.com');
        Gate::define('usePageBuilder', static fn (): bool => false);
        PageBuilderAccess::authorizeUsing(static fn (): bool => false);

        $this->actingAs($user)
            ->getJson(route('voodbuilder.admin.license.status'))
            ->assertForbidden();
    }

    public function test_status_returns_versioned_dashboard_payload(): void
    {
        $user = $this->makeUser('admin@example.com');
        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $response = $this->actingAs($user)
            ->getJson(route('voodbuilder.admin.license.status'));

        $response->assertOk()
            ->assertJsonPath('schema_version', LicenseDashboardPayload::SCHEMA_VERSION)
            ->assertJsonStructure([
                'schema_version',
                'driver',
                'licence' => [
                    'configured',
                    'key_masked',
                    'active',
                    'edition',
                    'is_paid_edition',
                ],
                'capabilities',
                'products' => [
                    'core',
                    'elements',
                    'components',
                    'templates',
                    'popups',
                    'vdocs',
                    'vtuts',
                    'vmedia',
                    'vcookiebar',
                ],
                'catalog' => [
                    'elements',
                    'page_templates',
                ],
                'cache_grace',
                'distribution',
            ]);

        $this->assertSame('vb_l****************5678', $response->json('licence.key_masked'));
        $this->assertSame('vb_l****************5678', $response->json('licence.identifier'));
        $payloadJson = (string) json_encode($response->json());
        $this->assertStringNotContainsString('vb_live_abcd1234efgh5678', $payloadJson);
        $this->assertSame('env', $response->json('catalog.elements.credential_source'));
    }

    public function test_catalog_resolver_prefers_anystack_snapshot_over_env(): void
    {
        Cache::forever(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY, [
            'edition' => 'professional',
            'active' => true,
            'capabilities' => ['editor.core'],
            'identifier' => 'seat-1',
            'expires_at' => null,
            'message' => null,
            'catalog_credentials' => [
                'elements' => 'anystack-elements-token',
            ],
            'fetched_at' => time(),
        ]);

        $this->assertSame('anystack-elements-token', CatalogCredentialResolver::elementsCredential());
        $this->assertSame('anystack', CatalogCredentialResolver::elementsStatus()['credential_source']);
    }

    public function test_refresh_clears_snapshot_and_returns_status(): void
    {
        Cache::forever(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY, [
            'edition' => 'agency',
            'active' => true,
            'capabilities' => ['editor.core'],
            'identifier' => 'old',
            'expires_at' => null,
            'message' => null,
            'fetched_at' => time(),
        ]);

        $user = $this->makeUser('refresh@example.com');
        Gate::define('usePageBuilder', static fn (): bool => true);
        PageBuilderAccess::authorizeUsing(static fn (): bool => true);

        $response = $this->actingAs($user)
            ->postJson(route('voodbuilder.admin.license.refresh'));

        $response->assertOk()
            ->assertJsonPath('refreshed', true)
            ->assertJsonPath('status.schema_version', LicenseDashboardPayload::SCHEMA_VERSION);

        // Config driver does not recreate AnyStack snapshot; key must be gone after forget.
        $this->assertNull(Cache::get(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY));
    }

    private function makeUser(string $email): User
    {
        $user = new User;
        $user->forceFill([
            'name' => 'License Admin',
            'email' => $email,
        ])->save();

        return $user;
    }
}
