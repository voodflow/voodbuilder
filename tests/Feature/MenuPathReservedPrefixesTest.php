<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Support\ReservedPathRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class MenuPathReservedPrefixesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.pages.enabled', true);
        $app['config']->set('voodbuilder.pages.route_prefix', '');
        $app['config']->set('voodbuilder.pages.menu_paths', true);

        // Third-party style: reserve before catch-alls register in Application::booted.
        $app->booting(function () use ($app): void {
            $app->make(ReservedPathRegistry::class)->reserve('acme-docs');
        });
    }

    public function test_vmedia_media_index_is_not_shadowed_by_nested_site_pages(): void
    {
        $this->assertTrue(Route::has('vmedia.media.index'));

        $nested = collect(Route::getRoutes())->first(
            fn ($route): bool => $route->getName() === 'voodbuilder.pages.show.nested',
        );

        $this->assertNotNull($nested);

        $request = Request::create('/vmedia/media', 'GET');

        $this->assertFalse(
            $nested->matches($request),
            'Nested site-page route must not match /vmedia/media',
        );

        $route = Route::getRoutes()->match($request);

        $this->assertSame('vmedia.media.index', $route->getName());
    }

    public function test_plugin_reserved_prefixes_are_not_shadowed_by_site_pages(): void
    {
        $this->assertContains('acme-docs', Voodbuilder::reservedPathPrefixes());

        $nested = collect(Route::getRoutes())->first(
            fn ($route): bool => $route->getName() === 'voodbuilder.pages.show.nested',
        );
        $flat = collect(Route::getRoutes())->first(
            fn ($route): bool => $route->getName() === 'voodbuilder.pages.show',
        );

        $this->assertNotNull($nested);
        $this->assertNotNull($flat);

        foreach (['tutorials', 'docs', 'acme-docs'] as $prefix) {
            if (! in_array($prefix, Voodbuilder::reservedPathPrefixes(), true) && $prefix !== 'acme-docs') {
                continue;
            }

            $this->assertFalse(
                $nested->matches(Request::create('/'.$prefix.'/sample-slug', 'GET')),
                "Nested site-page route must not match /{$prefix}/{slug}",
            );
            $this->assertFalse(
                $flat->matches(Request::create('/'.$prefix, 'GET')),
                "Flat site-page route must not match /{$prefix}",
            );
        }

        if (Route::has('vtuts.show')) {
            $this->assertSame(
                'vtuts.show',
                Route::getRoutes()->match(Request::create('/tutorials/introduction-to-pure-data', 'GET'))->getName(),
            );
        }
    }
}
