<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\SiteFooterSocialBlock;
use Voodflow\Voodbuilder\Support\MenuTablerIcons;
use Voodflow\Voodbuilder\Tests\TestCase;

class MenuSocialSupportTest extends TestCase
{
    public function test_it_exposes_common_tabler_social_icons(): void
    {
        $this->assertTrue(MenuTablerIcons::has('brand-facebook'));
        $this->assertNotNull(MenuTablerIcons::path('brand-facebook'));
    }

    public function test_social_footer_uses_social_menu_slot(): void
    {
        $html = SiteFooterSocialBlock::toPreviewHtml(SiteFooterSocialBlock::defaultConfig(), []);

        $this->assertStringContainsString('data-voodbuilder-menu="social"', $html);
        $this->assertStringNotContainsString('aria-label="Facebook"', $html);
    }

    public function test_social_footer_renders_tagline(): void
    {
        $visible = SiteFooterSocialBlock::toHtml(['show_tagline' => true], []);
        $hidden = SiteFooterSocialBlock::toHtml(['show_tagline' => false], []);

        $this->assertStringContainsString('data-voodbuilder-footer-tagline', $visible);
        $this->assertStringContainsString('data-voodbuilder-chrome="tagline"', $visible);
        $this->assertDoesNotMatchRegularExpression(
            '/data-voodbuilder-chrome="tagline"[^>]*\bhidden\b/',
            $visible,
        );
        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-chrome="tagline"[^>]*\bhidden\b|<[^>]*\bhidden\b[^>]*data-voodbuilder-chrome="tagline"/',
            $hidden,
        );
    }
}
