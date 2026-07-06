<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsSlotHydrator;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterCenteredBlock;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;
use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteFooterConfigTest extends TestCase
{
    #[Test]
    public function legacy_columns_setting_maps_to_per_column_flags(): void
    {
        $normalized = SiteFooterConfig::normalize(['columns' => 2]);

        $this->assertTrue($normalized['show_footer_col_1']);
        $this->assertTrue($normalized['show_footer_col_2']);
        $this->assertFalse($normalized['show_footer_col_3']);
        $this->assertFalse($normalized['show_footer_col_4']);
        $this->assertSame(2, $normalized['columns']);
    }

    #[Test]
    public function per_column_flags_can_hide_individual_columns(): void
    {
        $normalized = SiteFooterConfig::normalize([
            'show_footer_col_1' => false,
            'show_footer_col_2' => false,
            'show_footer_col_3' => false,
            'show_footer_col_4' => true,
        ]);

        $this->assertSame([4], SiteFooterConfig::visibleFooterColumnIndexes($normalized));
        $this->assertSame(1, $normalized['columns']);
    }

    #[Test]
    public function boolean_flags_are_normalized(): void
    {
        $normalized = SiteFooterConfig::normalize([
            'show_newsletter' => 0,
            'show_social' => 'yes',
            'show_footer_menu' => false,
            'show_copyright' => 1,
            'show_brand' => false,
            'show_tagline' => 1,
        ]);

        $this->assertFalse($normalized['show_newsletter']);
        $this->assertTrue($normalized['show_social']);
        $this->assertFalse($normalized['show_footer_menu']);
        $this->assertTrue($normalized['show_copyright']);
        $this->assertFalse($normalized['show_brand']);
        $this->assertTrue($normalized['show_tagline']);
    }

    #[Test]
    public function it_hides_brand_column_when_logo_and_copyright_are_disabled(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml([
            'show_brand' => false,
            'show_copyright' => false,
            'show_social' => false,
            'show_tagline' => false,
        ], []);

        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-chrome="brand-column"[^>]*\bhidden\b|<[^>]*\bhidden\b[^>]*data-voodbuilder-chrome="brand-column"/',
            $html,
        );
    }

    #[Test]
    public function it_renders_copyright_below_logo_in_column_footer(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml([], []);

        $this->assertStringContainsString('data-voodbuilder-chrome="brand"', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome="copyright"', $html);
        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-chrome="brand"[\s\S]*data-voodbuilder-chrome="copyright"/',
            $html,
        );
    }

    #[Test]
    public function all_footer_variants_expose_shared_chrome_options(): void
    {
        $centered = SiteFooterCenteredBlock::toHtml([], []);
        $columns = SiteFooterColumnsSimpleBlock::toHtml([], []);

        foreach ([$centered] as $html) {
            $this->assertStringContainsString('data-voodbuilder-chrome="brand"', $html);
            $this->assertStringContainsString('data-voodbuilder-chrome="copyright"', $html);
            $this->assertStringContainsString('data-voodbuilder-chrome="footer-menu"', $html);
            $this->assertStringContainsString('data-voodbuilder-chrome="social"', $html);
        }

        $this->assertStringContainsString('data-voodbuilder-footer-brand-col', $columns);
        $this->assertStringContainsString('data-voodbuilder-chrome="copyright"', $columns);
        $this->assertStringNotContainsString('data-voodbuilder-menu="footer"', $columns);
    }

    #[Test]
    public function column_title_uses_assigned_menu_name_when_available(): void
    {
        NavigationMenu::query()->create([
            'name' => 'Link Categorie',
            'slug' => 'footer_col_1',
            'locale' => 'en',
        ]);

        $this->assertSame('Link Categorie', SiteFooterColumnPlacements::columnTitle(1));
    }

    #[Test]
    public function it_renders_only_selected_footer_columns(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toPreviewHtml([
            'show_footer_col_1' => true,
            'show_footer_col_2' => false,
            'show_footer_col_3' => false,
            'show_footer_col_4' => true,
        ], []);

        $this->assertStringNotContainsString('data-voodbuilder-chrome="footer-col-1" data-voodbuilder-chrome-hidden', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome="footer-col-2"', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome-hidden', $html);
        $this->assertStringContainsString('md:col-start-1', $html);
        $this->assertStringContainsString('md:col-start-4', $html);
        $this->assertStringContainsString('data-voodbuilder-footer-menu-cols', $html);
        $this->assertStringNotContainsString('data-voodbuilder-menu="footer"', $html);
    }

    #[Test]
    public function it_redistributes_visible_footer_columns_when_enabled(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml([
            'show_footer_col_1' => true,
            'show_footer_col_2' => false,
            'show_footer_col_3' => false,
            'show_footer_col_4' => true,
            'footer_columns_redistribute' => true,
        ], []);

        $this->assertStringContainsString('data-voodbuilder-footer-columns-redistribute="1"', $html);
        $this->assertStringContainsString('md:flex-1', $html);
        $this->assertStringNotContainsString('md:col-start-4', $html);
    }

    #[Test]
    public function it_keeps_footer_column_slots_when_redistribute_is_disabled(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml([
            'show_footer_col_1' => true,
            'show_footer_col_2' => false,
            'show_footer_col_3' => false,
            'show_footer_col_4' => true,
            'footer_columns_redistribute' => false,
        ], []);

        $this->assertStringContainsString('data-voodbuilder-footer-columns-redistribute="0"', $html);
        $this->assertStringContainsString('md:grid-cols-4', $html);
        $this->assertStringContainsString('md:col-start-1', $html);
        $this->assertStringContainsString('md:col-start-4', $html);
        $this->assertStringNotContainsString('md:justify-end', $html);
    }

    #[Test]
    public function show_tagline_controls_tagline_independently_from_brand(): void
    {
        $visible = SiteFooterColumnsSimpleBlock::toHtml([
            'show_tagline' => true,
            'show_brand' => false,
        ], []);

        $hidden = SiteFooterColumnsSimpleBlock::toHtml([
            'show_tagline' => false,
            'show_brand' => true,
        ], []);

        $this->assertStringContainsString('data-voodbuilder-chrome="footer-tagline"', $visible);
        $this->assertDoesNotMatchRegularExpression(
            '/data-voodbuilder-chrome="footer-tagline"[^>]*\bhidden\b/',
            $visible,
        );
        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-chrome="footer-tagline"[^>]*\bhidden\b|<[^>]*\bhidden\b[^>]*data-voodbuilder-chrome="footer-tagline"/',
            $hidden,
        );
        $this->assertStringContainsString('data-voodbuilder-chrome="brand-column"', $visible);
        $this->assertDoesNotMatchRegularExpression(
            '/data-voodbuilder-chrome="brand-column"[^>]*\bhidden\b/',
            $visible,
        );
    }

    #[Test]
    public function centered_footer_tagline_is_independent_from_logo(): void
    {
        $html = SiteFooterCenteredBlock::toHtml([
            'show_brand' => false,
            'show_tagline' => true,
        ], []);

        $this->assertDoesNotMatchRegularExpression(
            '/data-voodbuilder-chrome="tagline"[^>]*\bhidden\b/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-chrome="brand"[^>]*\bhidden\b|<[^>]*\bhidden\b[^>]*data-voodbuilder-chrome="brand"/',
            $html,
        );
    }

    #[Test]
    public function it_aligns_a_single_visible_menu_column_to_the_right_when_redistributed(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml([
            'show_footer_col_1' => false,
            'show_footer_col_2' => false,
            'show_footer_col_3' => false,
            'show_footer_col_4' => true,
            'footer_columns_redistribute' => true,
        ], []);

        $this->assertStringContainsString('data-voodbuilder-footer-menu-cols', $html);
        $this->assertStringContainsString('md:justify-end', $html);
    }

    #[Test]
    public function it_hides_disabled_footer_chrome_on_frontend_render(): void
    {
        $html = SiteFooterCenteredBlock::toHtml([
            'show_footer_menu' => false,
            'show_copyright' => true,
        ], []);

        $this->assertStringContainsString('data-voodbuilder-chrome="footer-menu"', $html);
        $this->assertStringContainsString('hidden', $html);
        $this->assertStringNotContainsString('data-voodbuilder-chrome-hidden', $html);
    }

    #[Test]
    public function it_marks_disabled_footer_chrome_in_editor_preview(): void
    {
        $html = SiteFooterCenteredBlock::toPreviewHtml([
            'show_footer_menu' => false,
        ], []);

        $this->assertStringContainsString('data-voodbuilder-chrome-hidden', $html);
    }

    #[Test]
    public function it_applies_footer_chrome_visibility_when_hydrating_saved_html(): void
    {
        $saved = SiteFooterCenteredBlock::toPreviewHtml([], []);

        $hydrated = GrapesJsSlotHydrator::hydrateHtml($saved, false, [
            'show_footer_menu' => false,
            'show_copyright' => false,
        ]);

        $this->assertMatchesRegularExpression(
            '/<nav\b[^>]*\bhidden\b[^>]*data-voodbuilder-chrome="footer-menu"/',
            $hydrated,
        );
        $this->assertMatchesRegularExpression(
            '/<p\b[^>]*\bhidden\b[^>]*data-voodbuilder-chrome="copyright"/',
            $hydrated,
        );
    }

    #[Test]
    public function it_applies_per_column_visibility_when_hydrating_saved_html(): void
    {
        $saved = SiteFooterColumnsSimpleBlock::toHtml([], []);

        $hydrated = GrapesJsSlotHydrator::hydrateHtml($saved, false, [
            'show_footer_col_1' => true,
            'show_footer_col_2' => true,
            'show_footer_col_3' => false,
            'show_footer_col_4' => false,
        ]);

        $this->assertStringContainsString('data-voodbuilder-footer-col="3"', $hydrated);
        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-footer-col="3"[^>]*hidden|hidden[^>]*data-voodbuilder-footer-col="3"/',
            $hydrated,
        );
    }
}
