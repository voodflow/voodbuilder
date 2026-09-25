<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Enums\MenuDropdownLayout;
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

        Cache::put('voodbuilder.menu.v2.main', [
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

    #[Test]
    public function it_loads_the_menu_tree_in_one_items_query_and_keeps_mega_fields_when_cached(): void
    {
        $menu = NavigationMenu::query()->create(['name' => 'Header', 'slug' => 'main']);
        $products = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Products',
            'type' => MenuItemType::Url,
            'link' => '#',
            'dropdown_layout' => MenuDropdownLayout::Mega,
            'sort_order' => 0,
        ]);

        foreach (['Builder', 'Forms', 'Popups', 'Media'] as $index => $label) {
            NavigationMenuItem::query()->create([
                'menu_id' => $menu->id,
                'parent_id' => $products->id,
                'label' => $label,
                'description' => "{$label} description",
                'type' => MenuItemType::Url,
                'link' => '/' . strtolower($label),
                'sort_order' => $index,
            ]);
        }

        Navigation::clearCache();
        DB::enableQueryLog();
        $fresh = Navigation::items('main');
        $itemQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], (new NavigationMenuItem)->getTable()))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(1, $itemQueries);
        $this->assertSame(['Builder', 'Forms', 'Popups', 'Media'], $fresh->first()->children->pluck('label')->all());

        $cachedProducts = Navigation::items('main')->first();

        $this->assertSame(MenuDropdownLayout::Mega, $cachedProducts->dropdown_layout);
        $this->assertSame('Builder description', $cachedProducts->children->first()->description);
    }
}
