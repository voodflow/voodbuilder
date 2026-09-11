<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Enums\ApiDataSourceDriver;
use Voodflow\Voodbuilder\Models\ApiDataSource;
use Voodflow\Voodbuilder\Support\DataSources\ApiDataSourceManager;
use Voodflow\Voodbuilder\Support\DataSources\HttpUrlGuard;
use Voodflow\Voodbuilder\Support\DataSources\TokenResolver;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ApiDataSourceBindingRegistrar;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ApiDataSourceItemBindingSource;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ApiDataSourceRemoteBindingSource;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ApiDataSourceRow;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;
use Voodflow\VoodbuilderDynamicData\VoodbuilderDynamicData;

class ApiDataSourceTest extends TestCase
{
    public function test_http_url_guard_rejects_localhost(): void
    {
        $this->assertFalse(HttpUrlGuard::isSafeHttpUrl('http://localhost/api'));
        $this->assertFalse(HttpUrlGuard::isSafeHttpUrl('http://127.0.0.1/api'));
    }

    public function test_token_resolver_interpolates_context(): void
    {
        $resolved = TokenResolver::resolve(
            'https://api.example.com/items/{{route.id}}?q={{query}}',
            ['route' => ['id' => 42], 'query' => 'alpha'],
        );

        $this->assertSame('https://api.example.com/items/42?q=alpha', $resolved);
    }

    public function test_static_driver_resolves_primary_row_for_bindings(): void
    {
        $source = new ApiDataSource([
            'name' => 'Demo products',
            'slug' => 'products',
            'driver' => ApiDataSourceDriver::Static,
            'enabled' => true,
            'cache_ttl_seconds' => 0,
            'config' => [
                'rows' => [
                    [
                        'value' => 'sku-1',
                        'label' => 'Widget',
                        'meta' => [
                            'title' => 'Widget Pro',
                            'image' => 'https://cdn.example.com/w.png',
                        ],
                    ],
                ],
            ],
        ]);

        $manager = app(ApiDataSourceManager::class);
        $row = $manager->resolvePrimary($source);

        $this->assertNotNull($row);
        $this->assertSame('sku-1', $row['value']);
        $this->assertSame('Widget', $row['label']);
        $this->assertSame('Widget Pro', $row['meta']['title']);

        $binding = new ApiDataSourceRemoteBindingSource($source);
        $this->assertSame('products.remote', $binding->id());
        $this->assertSame('Widget Pro', $binding->resolve('title', BindingContext::forPage(null)));
        $this->assertSame('https://cdn.example.com/w.png', $binding->resolve('image', BindingContext::forPage(null)));
    }

    public function test_list_item_binding_resolves_from_api_row(): void
    {
        $source = new ApiDataSource([
            'name' => 'Demo products',
            'slug' => 'products',
            'driver' => ApiDataSourceDriver::Static,
            'enabled' => true,
            'cache_ttl_seconds' => 0,
            'config' => [
                'rows' => [
                    [
                        'value' => 'sku-1',
                        'label' => 'Widget',
                        'meta' => ['title' => 'Widget Pro'],
                    ],
                ],
            ],
        ]);

        $row = ApiDataSourceRow::fromMappedRow('products', [
            'value' => 'sku-1',
            'label' => 'Widget',
            'meta' => ['title' => 'Widget Pro'],
        ]);

        $binding = new ApiDataSourceItemBindingSource($source);
        $this->assertSame('products.item', $binding->id());
        $this->assertSame(
            'Widget Pro',
            $binding->resolve('title', BindingContext::forPage(null, $row)),
        );
    }

    public function test_binding_registry_rewrites_remote_to_item_inside_repeat(): void
    {
        $source = new ApiDataSource([
            'name' => 'Demo products',
            'slug' => 'products',
            'driver' => ApiDataSourceDriver::Static,
            'enabled' => true,
            'cache_ttl_seconds' => 0,
            'config' => [
                'rows' => [[
                    'value' => 'sku-1',
                    'label' => 'Widget',
                    'meta' => ['title' => 'Widget Pro'],
                ]],
            ],
        ]);

        $row = ApiDataSourceRow::fromMappedRow('products', [
            'value' => 'sku-1',
            'label' => 'Widget',
            'meta' => ['title' => 'Widget Pro'],
        ]);

        $registry = app(BindingRegistry::class);
        $registry->register(new ApiDataSourceItemBindingSource($source));

        $this->assertSame(
            'Widget Pro',
            $registry->resolve('products.remote.title', BindingContext::forPage(null, $row)),
        );
    }

    public function test_callback_driver_resolves_registered_callback(): void
    {
        Voodbuilder::registerApiDataSourceCallback('demo-feed', function (array $params, array $config): array {
            return [
                ['value' => 'a', 'label' => 'Alpha', 'meta' => ['title' => 'A']],
                ['value' => 'b', 'label' => 'Beta', 'meta' => ['title' => 'B']],
            ];
        });

        $source = new ApiDataSource([
            'name' => 'Callback feed',
            'slug' => 'feed',
            'driver' => ApiDataSourceDriver::Callback,
            'enabled' => true,
            'cache_ttl_seconds' => 0,
            'config' => ['callback' => 'demo-feed'],
        ]);

        $rows = app(ApiDataSourceManager::class)->resolve($source);

        $this->assertCount(2, $rows);
        $this->assertSame('Alpha', $rows[0]['label']);
    }

    public function test_http_driver_maps_response_paths_and_tokens(): void
    {
        Http::fake([
            'https://api.example.com/items/9*' => Http::response([
                'data' => [
                    ['id' => 9, 'name' => 'Alpha', 'cover' => 'https://cdn.example.com/a.jpg'],
                ],
            ], 200),
        ]);

        $source = new ApiDataSource([
            'name' => 'Remote items',
            'slug' => 'items',
            'driver' => ApiDataSourceDriver::Http,
            'enabled' => true,
            'cache_ttl_seconds' => 0,
            'config' => [
                'url' => 'https://api.example.com/items/{{route.id}}',
                'method' => 'GET',
                'response_path' => 'data',
                'value_path' => 'id',
                'label_path' => 'name',
                'meta_paths' => [
                    'title' => 'name',
                    'image' => 'cover',
                ],
                'local_filter' => false,
            ],
        ]);

        config(['voodbuilder.api_data_sources.http.block_ssrf' => false]);

        $row = app(ApiDataSourceManager::class)->resolvePrimary($source, [
            'route' => ['id' => 9],
        ]);

        $this->assertNotNull($row);
        $this->assertSame(9, $row['value']);
        $this->assertSame('Alpha', $row['label']);
        $this->assertSame('Alpha', $row['meta']['title']);
        $this->assertSame('https://cdn.example.com/a.jpg', $row['meta']['image']);
    }

    public function test_repeat_list_registers_when_dynamic_data_is_active(): void
    {
        VoodbuilderDynamicData::activate();

        $source = ApiDataSource::query()->create([
            'name' => 'Demo products',
            'slug' => 'products-list-demo',
            'driver' => ApiDataSourceDriver::Static,
            'enabled' => true,
            'cache_ttl_seconds' => 0,
            'config' => [
                'rows' => [
                    ['value' => '1', 'label' => 'One', 'meta' => ['title' => 'T1']],
                    ['value' => '2', 'label' => 'Two', 'meta' => ['title' => 'T2']],
                ],
            ],
        ]);

        app(ApiDataSourceBindingRegistrar::class)
            ->register($source);

        $this->assertTrue(app(RepeatListRegistry::class)->has('products-list-demo.list'));

        $records = app(RepeatListRegistry::class)->resolve('products-list-demo.list', 10, 'label', 'asc');

        $this->assertCount(2, $records);
        $this->assertInstanceOf(ApiDataSourceRow::class, $records[0]);
        $this->assertSame('One', $records[0]->getAttribute('label'));
    }

    public function test_http_driver_auto_exposes_scalar_fields_from_dummyjson_shape(): void
    {
        Http::fake([
            'https://dummyjson.com/users*' => Http::response([
                'users' => [
                    [
                        'id' => 1,
                        'firstName' => 'Emily',
                        'lastName' => 'Johnson',
                        'email' => 'emily@example.com',
                        'image' => 'https://cdn.example.com/e.jpg',
                        'address' => ['city' => 'Phoenix'],
                    ],
                ],
                'total' => 1,
            ], 200),
        ]);

        $source = new ApiDataSource([
            'name' => 'Users',
            'slug' => 'users',
            'driver' => ApiDataSourceDriver::Http,
            'enabled' => true,
            'cache_ttl_seconds' => 0,
            'config' => [
                'url' => 'https://dummyjson.com/users',
                'method' => 'GET',
                'response_path' => 'data',
                'value_path' => 'id',
                'label_path' => 'name',
                'expose_all_fields' => true,
                'local_filter' => false,
            ],
        ]);

        config(['voodbuilder.api_data_sources.http.block_ssrf' => false]);

        $row = app(ApiDataSourceManager::class)->resolvePrimary($source);

        $this->assertNotNull($row);
        $this->assertSame(1, $row['value']);
        $this->assertSame('Emily Johnson', $row['label']);
        $this->assertSame('Emily', $row['meta']['firstName']);
        $this->assertSame('emily@example.com', $row['meta']['email']);
        $this->assertSame('Phoenix', $row['meta']['address.city']);

        $fields = $source->bindableFieldIds();
        $this->assertContains('firstName', $fields);
        $this->assertContains('email', $fields);
        $this->assertContains('image', $fields);
    }
}
