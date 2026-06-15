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

    public function test_group_items_resolve_to_hash_without_a_link(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Soundmit 2026',
            'type' => MenuItemType::Group,
            'link' => null,
        ]);

        $this->assertSame('#', $item->resolveUrl());
        $this->assertFalse($item->hasResolvableLink());
    }

    public function test_parent_is_active_when_a_child_matches_the_current_route(): void
    {
        Route::get('/events/{slug}', fn () => 'event')->name('vevents.show');

        $child = new NavigationMenuItem([
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.show',
            'route_parameters' => ['slug' => 'soundmit-2026'],
            'route_match' => 'vevents.*',
        ]);

        $parent = new NavigationMenuItem([
            'label' => 'Soundmit 2026',
            'type' => MenuItemType::Group,
        ]);
        $parent->setRelation('children', collect([$child]));

        $this->get('/events/soundmit-2026');

        $this->assertTrue($child->isActive());
        $this->assertTrue($parent->isActive());
    }
}
