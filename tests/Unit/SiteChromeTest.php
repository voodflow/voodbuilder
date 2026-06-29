<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingFooterBlock;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SiteChrome;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteChromeTest extends TestCase
{
    public function test_page_level_hide_flags_are_respected(): void
    {
        $page = new SitePage([
            'layout' => 'landing',
            'hide_site_nav' => true,
            'hide_site_footer' => true,
        ]);

        $this->assertTrue(SiteChrome::shouldHideNav($page));
        $this->assertTrue(SiteChrome::shouldHideFooter($page));
    }

    public function test_grapesjs_editor_keeps_site_navigation_visible(): void
    {
        $page = new SitePage([
            'layout' => 'home',
            'sub_theme' => 'site',
        ]);

        $this->assertFalse(SiteChrome::shouldHideNav($page, grapesJsEditor: true));
    }

    public function test_sub_theme_chrome_defaults_apply_on_landing_layout(): void
    {
        $this->app->make(SubThemeRegistry::class)->register('chrome-demo', [
            'label' => 'Chrome demo',
            'chrome' => [
                'hide_site_nav' => true,
                'hide_site_footer' => true,
            ],
        ]);

        $page = new SitePage([
            'layout' => 'landing',
            'sub_theme' => 'chrome-demo',
        ]);

        $this->assertTrue(SiteChrome::shouldHideNav($page));
        $this->assertTrue(SiteChrome::shouldHideFooter($page));
    }

    public function test_tailblocks_footer_variant_renders_dynamic_markup(): void
    {
        $html = LandingFooterBlock::toHtml([
            'variant' => 'a',
            'brand_name' => 'Acme',
            'column_1_title' => 'Products',
            'menu_columns' => [],
        ], []);

        $this->assertStringContainsString('vp-landing-footer-tailblocks--a', $html);
        $this->assertStringContainsString('Acme', $html);
        $this->assertStringContainsString('Products', $html);
    }
}
