<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageMenuPath;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageMenuPathTest extends TestCase
{
    public function test_nested_menu_page_builds_section_slug_path(): void
    {
        Config::set('voodbuilder.pages.menu_paths', true);
        Config::set('voodbuilder.pages.route_prefix', '');

        $page = SitePage::query()->create([
            'slug' => 'a-voodflow',
            'title' => 'Voodflow',
            'locale' => 'en',
            'published' => true,
        ]);

        $menu = NavigationMenu::query()->create([
            'slug' => 'main',
            'locale' => 'en',
            'name' => 'Main',
        ]);

        $group = NavigationMenuItem::query()->create([
            'menu_id' => $menu->getKey(),
            'label' => 'Products',
            'type' => MenuItemType::Group,
            'sort_order' => 0,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->getKey(),
            'parent_id' => $group->getKey(),
            'label' => 'Voodflow',
            'type' => MenuItemType::Page,
            'link' => 'a-voodflow',
            'sort_order' => 0,
        ]);

        $this->assertSame('products/a-voodflow', SitePageMenuPath::relativePath($page));
        $this->assertSame('a-voodflow', SitePageMenuPath::resolveSlugFromRoute('products', 'a-voodflow'));
    }

    public function test_menu_paths_disabled_returns_flat_slug(): void
    {
        Config::set('voodbuilder.pages.menu_paths', false);

        $page = SitePage::query()->create([
            'slug' => 'about',
            'title' => 'About',
            'locale' => 'en',
            'published' => true,
        ]);

        $this->assertSame('about', SitePageMenuPath::relativePath($page));
    }
}
