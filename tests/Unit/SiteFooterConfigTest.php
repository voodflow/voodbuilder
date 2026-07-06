<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteFooterConfigTest extends TestCase
{
    public function test_columns_are_clamped_between_one_and_four(): void
    {
        $normalized = SiteFooterConfig::normalize(['columns' => 9]);

        $this->assertSame(4, $normalized['columns']);
    }

    public function test_boolean_flags_are_normalized(): void
    {
        $normalized = SiteFooterConfig::normalize([
            'show_newsletter' => 0,
            'show_social' => 'yes',
            'show_footer_menu' => false,
            'show_copyright' => 1,
        ]);

        $this->assertFalse($normalized['show_newsletter']);
        $this->assertTrue($normalized['show_social']);
        $this->assertFalse($normalized['show_footer_menu']);
        $this->assertTrue($normalized['show_copyright']);
    }

    public function test_preview_html_hides_extra_footer_columns(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toPreviewHtml(['columns' => 2], []);

        $this->assertStringContainsString('data-voodbuilder-footer-col="3"', $html);
        $this->assertStringContainsString('hidden', $html);
    }
}
