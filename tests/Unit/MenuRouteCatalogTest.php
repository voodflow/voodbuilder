<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Support\MenuRouteCatalog;
use Voodflow\Voodbuilder\Tests\TestCase;

class MenuRouteCatalogTest extends TestCase
{
    protected function defineWebRoutes($router): void
    {
        $router->get('/', fn () => 'home')->name('home');
        $router->get('/tutorials', fn () => 'tutorials')->name('vtuts.index');
        $router->get('/tutorials/{slug}', fn () => 'show')->name('vtuts.show');
        $router->get('/tutorials/series/{seriesSlug}/{vtutSlug}', fn () => 'lesson')->name('vtuts.series.lesson');
        $router->get('/admin', fn () => 'admin')->name('filament.admin.pages.dashboard');
    }

    public function test_builds_active_patterns_for_route_groups(): void
    {
        $this->assertSame('home', MenuRouteCatalog::activePattern('home'));
        $this->assertSame('vtuts.*', MenuRouteCatalog::activePattern('vtuts.index'));
        $this->assertSame('vtuts.*', MenuRouteCatalog::activePattern('vtuts.show'));
        $this->assertSame('vtuts.series.*', MenuRouteCatalog::activePattern('vtuts.series.lesson'));
    }

    public function test_excludes_internal_routes_from_options(): void
    {
        $options = MenuRouteCatalog::options();

        $this->assertArrayHasKey('home', $options);
        $this->assertArrayHasKey('vtuts.index', $options);
        $this->assertArrayHasKey('vtuts.show', $options);
        $this->assertArrayNotHasKey('filament.admin.pages.dashboard', $options);
    }

    public function test_lists_required_route_parameters(): void
    {
        $this->assertSame(['slug'], MenuRouteCatalog::requiredParameterNames('vtuts.show'));
        $this->assertSame(['seriesSlug', 'vtutSlug'], MenuRouteCatalog::requiredParameterNames('vtuts.series.lesson'));
        $this->assertSame([], MenuRouteCatalog::requiredParameterNames('home'));
    }

    public function test_builds_active_patterns_for_event_routes(): void
    {
        Route::get('/events/{slug}/report', fn () => 'report')->name('vevents.report');

        $this->assertSame('vevents.*', MenuRouteCatalog::activePattern('vevents.report'));
    }
}
