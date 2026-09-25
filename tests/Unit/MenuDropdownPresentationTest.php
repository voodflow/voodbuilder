<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\MenuDropdownLayout;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Tests\TestCase;

class MenuDropdownPresentationTest extends TestCase
{
    public function test_auto_layout_uses_mega_when_children_have_descriptions(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        $group = NavigationMenuItem::query()->create([
            'menu_id' => $menu->getKey(),
            'label' => 'Products',
            'type' => MenuItemType::Group,
            'dropdown_layout' => MenuDropdownLayout::Auto,
            'sort_order' => 0,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->getKey(),
            'parent_id' => $group->getKey(),
            'label' => 'VoodBuilder',
            'description' => 'Visual site builder',
            'icon' => 'layout-dashboard',
            'type' => MenuItemType::Url,
            'link' => '/products/voodbuilder',
            'sort_order' => 0,
        ]);

        $group->load('children');

        $this->assertTrue($group->usesMegaDropdown());
        $this->assertSame(MenuDropdownLayout::Mega, $group->resolvedDropdownLayout());

        $html = view('voodbuilder::components.menu-nav-item', [
            'item' => $group,
        ])->render();

        $this->assertStringContainsString('voodbuilder-dropdown-panel--mega', $html);
        $this->assertStringContainsString('data-layout="mega"', $html);
        $this->assertStringContainsString('voodbuilder-nav-menu-item__description', $html);
        $this->assertStringContainsString('Visual site builder', $html);
        $this->assertStringContainsString('voodbuilder-nav-menu-item__icon', $html);
    }

    public function test_list_layout_stays_compact_without_rich_children(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        $group = NavigationMenuItem::query()->create([
            'menu_id' => $menu->getKey(),
            'label' => 'Links',
            'type' => MenuItemType::Group,
            'dropdown_layout' => MenuDropdownLayout::List,
            'sort_order' => 0,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->getKey(),
            'parent_id' => $group->getKey(),
            'label' => 'About',
            'type' => MenuItemType::Url,
            'link' => '/about',
            'sort_order' => 0,
        ]);

        $group->load('children');

        $this->assertFalse($group->usesMegaDropdown());

        $html = view('voodbuilder::components.menu-nav-item', [
            'item' => $group,
        ])->render();

        $this->assertStringNotContainsString('voodbuilder-dropdown-panel--mega', $html);
        $this->assertStringContainsString('data-layout="list"', $html);
        $this->assertStringContainsString('About', $html);
    }
}
