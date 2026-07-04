<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavConfig;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavSimpleBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteNavConfigTest extends TestCase
{
    public function test_legacy_search_variant_enables_search_flag(): void
    {
        $normalized = SiteNavConfig::normalize([
            'variant' => 'with_search_dark',
            'show_search' => false,
        ]);

        $this->assertTrue($normalized['show_search']);
        $this->assertSame('simple', $normalized['variant']);
    }

    public function test_centered_links_variant_sets_center_alignment(): void
    {
        $normalized = SiteNavConfig::normalize([
            'variant' => 'centered_links',
        ]);

        $this->assertSame('center', $normalized['main_nav_align']);
    }

    public function test_preview_html_uses_icon_buttons_without_search_form(): void
    {
        $html = SiteNavSimpleBlock::toPreviewHtml(SiteNavSimpleBlock::defaultConfig(), []);

        $this->assertStringContainsString('voodbuilder-header-icon-btn', $html);
        $this->assertStringContainsString('data-gjs-type="default"', $html);
        $this->assertStringContainsString('data-voodbuilder-search', $html);
        $this->assertSame(3, substr_count($html, 'voodbuilder-header-icon-btn'));
        $this->assertStringContainsString('data-mobile-nav-toggle', $html);
        $this->assertStringNotContainsString('data-voodbuilder-search-form', $html);
    }
}
