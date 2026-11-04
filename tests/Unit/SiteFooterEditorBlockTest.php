<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\Editor\EditorSlotHydrator;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterBlocks;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterColumnsContactBlock;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;
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
    public function it_renders_the_contact_footer_with_static_columns(): void
    {
        $html = SiteFooterColumnsContactBlock::toHtml([
            'contact_title' => 'Contatti',
            'contact_lines' => ['led@polito.it', '+39 011 090 4000', 'Corso Duca degli Abruzzi, 24'],
            'details_title' => 'Dettagli',
            'details_lines' => ['DET - Dipartimento di Elettronica', '© Politecnico di Torino'],
        ], []);

        $this->assertStringContainsString('data-voodbuilder-footer-text-col="contact"', $html);
        $this->assertStringContainsString('data-voodbuilder-footer-text-col="details"', $html);
        $this->assertStringContainsString('mailto:led@polito.it', $html);
        $this->assertStringContainsString('tel:+390110904000', $html);
        $this->assertStringContainsString('Corso Duca degli Abruzzi, 24', $html);
        $this->assertStringNotContainsString('href="Corso Duca degli Abruzzi, 24"', $html);
        $this->assertStringContainsString('DET - Dipartimento di Elettronica', $html);
        // Two menu columns only, the static columns replace menu columns 3 and 4.
        $this->assertStringContainsString('data-voodbuilder-menu="footer_col_2"', $html);
        $this->assertStringNotContainsString('lg:col-start-1', $html);
    }

    #[Test]
    public function it_refreshes_saved_contact_rows_from_the_block_config(): void
    {
        $config = static fn (array $items): array => SiteFooterConfig::normalize([
            'contact_title' => 'Contatti',
            'contact_items' => $items,
        ]);

        $saved = SiteFooterColumnsContactBlock::toHtml($config([
            ['icon' => 'mail', 'text' => 'led@polito.it'],
            ['icon' => 'phone', 'text' => '+39 011 090 4000'],
            ['icon' => 'map-pin', 'text' => 'Corso Duca degli Abruzzi, 24'],
            ['icon' => '', 'text' => '10129 Torino'],
        ]), []);

        $freshConfig = $config([
            ['icon' => 'mail', 'text' => 'led@polito.it'],
            ['icon' => 'phone', 'text' => '+39 011 090 4000'],
            ['icon' => 'map-pin', 'text' => 'Corso Duca degli Abruzzi, 24 10129 Torino'],
        ]);

        // GrapesJS serialises the rendered rows into the layout HTML, so the saved
        // markup still carries the old rows while the config attribute is current.
        $saved = (string) preg_replace(
            '/data-voodbuilder-config=([\'"]).*?\1/s',
            "data-voodbuilder-config='" . htmlspecialchars(
                json_encode($freshConfig, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ENT_QUOTES | ENT_HTML5,
            ) . "'",
            $saved,
            1,
        );

        $hydrated = EditorSlotHydrator::hydrateHtml($saved);

        $this->assertStringContainsString('Corso Duca degli Abruzzi, 24 10129 Torino', $hydrated);
        $this->assertSame(3, substr_count($hydrated, 'data-voodbuilder-footer-text-item'));
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
