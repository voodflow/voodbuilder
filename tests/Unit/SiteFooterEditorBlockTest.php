<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\Editor\EditorSlotHydrator;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterBlocks;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\Editor\SiteNavBlocks;
use Voodflow\Voodbuilder\Support\Editor\SiteNavSimpleBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteFooterEditorBlockTest extends TestCase
{
    #[Test]
    public function it_treats_only_prefixed_ids_as_footer_variants(): void
    {
        $this->assertFalse(SiteFooterBlocks::isFooterBlockId('site_footer'));
        $this->assertTrue(SiteFooterBlocks::isFooterBlockId('site_footer_columns_simple'));
    }

    #[Test]
    public function it_renders_footer_columns_with_menu_slots(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml([
            'show_footer_col_1' => true,
            'show_footer_col_2' => true,
            'show_footer_col_3' => true,
            'show_footer_col_4' => false,
        ], []);

        $this->assertStringContainsString('data-voodbuilder-menu="footer_col_1"', $html);
        $this->assertStringContainsString('data-voodbuilder-footer-col="4"', $html);
        // Brand slot is hydrated then the empty marker attribute is stripped.
        $this->assertDoesNotMatchRegularExpression('/\sdata-voodbuilder-brand(?:=|\s|>)/', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome="brand"', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome-part="logo"', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome="footer-tagline"', $html);
        $this->assertStringContainsString('hidden', $html);
    }

    #[Test]
    public function site_nav_blocks_are_registered(): void
    {
        $this->assertContains(SiteNavSimpleBlock::class, SiteNavBlocks::blockClasses());
    }

    #[Test]
    public function it_renders_inline_footer_menu_links_in_a_row(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Footer inline',
            'slug' => 'footer',
            'locale' => 'en',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => '/',
            'sort_order' => 0,
        ]);

        $html = EditorSlotHydrator::renderMenuList('footer');

        $this->assertStringContainsString('flex flex-wrap', $html);
    }

    #[Test]
    public function it_renders_column_footer_menu_links_stacked(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Footer column 1',
            'slug' => 'footer_col_1',
            'locale' => 'en',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => '/',
            'sort_order' => 0,
        ]);

        $html = EditorSlotHydrator::renderMenuList('footer_col_1');

        $this->assertStringContainsString('space-y-2', $html);
        $this->assertStringNotContainsString('flex flex-wrap', $html);
    }
}
