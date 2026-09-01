<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Database\Seeders\VoodflowMarketingSiteSeeder;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MarketingSiteContent;
use Voodflow\Voodbuilder\Support\MarketingSiteLayout;
use Voodflow\Voodbuilder\Support\MarketingSiteMenus;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodflowMarketingSiteSeederTest extends TestCase
{
    #[Test]
    public function test_it_seeds_marketing_layout_pages_and_main_menu(): void
    {
        SitePage::query()->create([
            'title' => 'Stale doc page',
            'slug' => 'a-docs',
            'locale' => 'en',
            'published' => true,
        ]);

        $this->seed(VoodflowMarketingSiteSeeder::class);

        $this->assertSame(count(MarketingSiteContent::pages()), SitePage::query()->where('slug', 'like', 'a%')->count());
        $this->assertNull(SitePage::query()->where('slug', 'a-docs')->first());

        $home = SitePage::query()->where('slug', 'a')->first();
        $this->assertNotNull($home);
        $this->assertFalse($home->is_home);
        $this->assertNotNull($home->chrome_layout_id);

        $layout = ChromeLayout::query()->where('slug', MarketingSiteLayout::SLUG)->first();
        $this->assertNotNull($layout);
        $this->assertContains('pages', $layout->assignedChannelIds());
        $this->assertStringContainsString('data-voodbuilder-content-slot', (string) $layout->html);
        $this->assertStringContainsString('site_nav_simple', (string) $layout->html);

        $main = NavigationMenu::query()
            ->where('slug', 'main')
            ->where('locale', MarketingSiteMenus::LOCALE)
            ->first();

        $this->assertNotNull($main);
        $rootItems = $main->rootItems()->get();
        $this->assertCount(3, $rootItems);

        $products = $rootItems->firstWhere('label', 'Products');
        $this->assertNotNull($products);
        $this->assertSame(MenuItemType::Group, $products->type);
        $this->assertSame(
            count(MarketingSiteContent::productLandings()),
            $products->children()->count(),
        );

        $homeItem = NavigationMenuItem::query()
            ->where('menu_id', $main->getKey())
            ->whereNull('parent_id')
            ->where('label', 'Home')
            ->first();

        $this->assertNotNull($homeItem);
        $this->assertSame('a', $homeItem->link);
    }
}
