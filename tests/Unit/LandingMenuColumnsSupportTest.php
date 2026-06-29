<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\LandingMenuColumnsSupport;
use Voodflow\Voodbuilder\Support\LandingMenuPlacements;
use Voodflow\Voodbuilder\Support\Navigation;
use Voodflow\Voodbuilder\Tests\TestCase;

class LandingMenuColumnsSupportTest extends TestCase
{
    public function test_resolves_four_columns_from_menu_placements(): void
    {
        for ($index = 1; $index <= LandingMenuPlacements::FOOTER_COLUMN_COUNT; $index++) {
            $menu = NavigationMenu::query()->create([
                'name' => "Column {$index}",
                'slug' => LandingMenuPlacements::footerColumnSlug($index),
            ]);

            NavigationMenuItem::query()->create([
                'menu_id' => $menu->id,
                'label' => "Link {$index}",
                'type' => MenuItemType::Url,
                'link' => "https://example.test/{$index}",
                'sort_order' => 1,
            ]);
        }

        Navigation::clearCache();

        $columns = LandingMenuColumnsSupport::columnsFromPlacements([], 'landing_footer', 4);

        $this->assertCount(4, $columns);
        $this->assertSame('Column 1', $columns[0]['title']);
        $this->assertSame('https://example.test/2', $columns[1]['links'][0]['url']);
    }

    public function test_column_title_override_takes_precedence_over_menu_name(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Menu name',
            'slug' => LandingMenuPlacements::footerColumnSlug(1),
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'About us',
            'type' => MenuItemType::Url,
            'link' => 'https://example.test/about',
            'sort_order' => 1,
        ]);

        Navigation::clearCache();

        $columns = LandingMenuColumnsSupport::columnsFromPlacements([
            'column_1_title' => 'Custom title',
        ], 'landing_footer', 4);

        $this->assertCount(1, $columns);
        $this->assertSame('Custom title', $columns[0]['title']);
    }
}
