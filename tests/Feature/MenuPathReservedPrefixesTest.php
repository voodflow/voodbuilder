<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Tests\TestCase;

class MenuPathReservedPrefixesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.pages.enabled', true);
        $app['config']->set('voodbuilder.pages.route_prefix', '');
        $app['config']->set('voodbuilder.pages.menu_paths', true);
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
}
