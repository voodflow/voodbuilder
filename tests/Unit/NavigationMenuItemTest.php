<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Voodflow\Vpress\Enums\MenuItemType;
use Voodflow\Vpress\Models\NavigationMenuItem;
use Voodflow\Vpress\Tests\TestCase;

class NavigationMenuItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/events/{slug}/report', fn () => 'report')->name('vevents.report');
    }

    public function test_resolves_parameterized_route_urls(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.report',
            'route_parameters' => ['slug' => 'soundmit-2026'],
        ]);

        $this->assertSame(
            url('/events/soundmit-2026/report'),
            $item->resolveUrl(),
        );
    }

    public function test_returns_hash_when_route_parameters_are_missing(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.report',
        ]);

        $this->assertSame('#', $item->resolveUrl());
    }
}
