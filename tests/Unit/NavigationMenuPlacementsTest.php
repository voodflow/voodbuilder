<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\NavigationMenuPlacements;
use Voodflow\Voodbuilder\Tests\TestCase;

class NavigationMenuPlacementsTest extends TestCase
{
    #[Test]
    public function test_it_exposes_site_header_and_footer_placements_only(): void
    {
        $options = NavigationMenuPlacements::flatOptions();

        $this->assertSame([
            'main',
            'header_extra',
            'social',
            'footer',
            'footer_col_1',
            'footer_col_2',
            'footer_col_3',
            'footer_col_4',
        ], array_keys($options));
    }

    #[Test]
    public function test_it_hides_deprecated_landing_placements_by_default(): void
    {
        $options = NavigationMenuPlacements::flatOptions();

        $this->assertArrayNotHasKey('landing_nav', $options);
        $this->assertArrayNotHasKey('landing_footer', $options);
        $this->assertArrayNotHasKey('landing_footer_col_1', $options);
    }

    #[Test]
    public function test_it_shows_deprecated_placement_when_editing_legacy_menu(): void
    {
        $menu = new NavigationMenu(['slug' => 'landing_footer_col_2']);

        $options = NavigationMenuPlacements::flatOptions($menu);

        $this->assertArrayHasKey('landing_footer_col_2', $options);
    }

    #[Test]
    public function test_it_groups_footer_placements_by_usage(): void
    {
        $options = NavigationMenuPlacements::formOptions();

        $this->assertArrayHasKey(__('voodbuilder::admin.menu_placements.groups.header'), $options);
        $this->assertArrayHasKey(__('voodbuilder::admin.menu_placements.groups.footer_columns'), $options);
        $this->assertArrayHasKey(__('voodbuilder::admin.menu_placements.groups.footer'), $options);
        $this->assertArrayHasKey('footer_col_1', $options[__('voodbuilder::admin.menu_placements.groups.footer_columns')]);
        $this->assertArrayHasKey('social', $options[__('voodbuilder::admin.menu_placements.groups.footer')]);
    }
}
