<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Database\Seeders\VoodflowMarketingSiteSeeder;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MarketingSiteContent;
use Voodflow\Voodbuilder\Support\MarketingSiteMenus;
use Voodflow\Voodbuilder\Support\Navigation;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodflowMarketingSiteSeederTest extends TestCase
{
    #[Test]
    public function test_it_seeds_marketing_pages_and_main_menu(): void
    {
        $this->seed(VoodflowMarketingSiteSeeder::class);

        $this->assertSame(count(MarketingSiteContent::pages()), SitePage::query()->where('slug', 'like', 'a%')->count());
        $this->assertNotNull(SitePage::query()->where('slug', 'a')->first());

        $main = NavigationMenu::query()
            ->where('slug', 'main')
            ->where('locale', MarketingSiteMenus::LOCALE)
            ->first();

        $this->assertNotNull($main);
        $this->assertCount(4, $main->rootItems);

        $plugins = $main->rootItems->firstWhere('label', 'Plugins');
        $this->assertNotNull($plugins);
        $this->assertSame(MenuItemType::Group, $plugins->type);
        $this->assertSame(
            count(MarketingSiteContent::pluginCatalog()) + 1,
            $plugins->children()->count(),
        );

        $home = Navigation::items('main')->first();
        $this->assertSame('Home', $home?->label);
        $this->assertStringContainsString('/pages/a', (string) $home?->resolveUrl());
    }
}
