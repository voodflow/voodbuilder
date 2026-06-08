<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Voodflow\Vpress\Support\MenuRouteCatalog;
use Voodflow\Vpress\Tests\TestCase;

class MenuRouteCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/', fn () => 'home')->name('home');
        Route::get('/tutorials', fn () => 'tutorials')->name('vtuts.index');
        Route::get('/tutorials/{slug}', fn () => 'show')->name('vtuts.show');
        Route::get('/tutorials/series/{seriesSlug}/{vtutSlug}', fn () => 'lesson')->name('vtuts.series.lesson');
        Route::get('/admin', fn () => 'admin')->name('filament.admin.pages.dashboard');
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
        $this->assertArrayNotHasKey('filament.admin.pages.dashboard', $options);
    }
}
