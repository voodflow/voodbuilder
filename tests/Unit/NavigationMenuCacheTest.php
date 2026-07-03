<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\Navigation;
use Voodflow\Voodbuilder\Tests\TestCase;

class NavigationMenuCacheTest extends TestCase
{
    #[Test]
    public function it_clears_stale_cache_when_menu_placement_slug_changes(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Header',
            'slug' => 'main',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => '/',
            'sort_order' => 0,
        ]);

        $this->assertCount(1, Navigation::items('main'));
        $this->assertCount(0, Navigation::items('header_extra'));

        $menu->update(['slug' => 'header_extra']);

        $this->assertCount(0, Navigation::items('main'));
        $this->assertCount(1, Navigation::items('header_extra'));
    }

    #[Test]
    public function it_ignores_stale_cache_when_menu_no_longer_exists_for_slug(): void
    {
        NavigationMenu::query()->create([
            'name' => 'Header',
            'slug' => 'header_extra',
        ]);

        \Illuminate\Support\Facades\Cache::put('voodbuilder.menu.main', [
            [
                'label' => 'Stale',
                'type' => MenuItemType::Url->value,
                'link' => '/stale',
                'route_parameters' => null,
                'route_match' => null,
                'open_in_new_tab' => false,
                'sort_order' => 0,
                'children' => [],
            ],
        ], 3600);

        $this->assertCount(0, Navigation::items('main'));
    }
}
