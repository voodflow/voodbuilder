<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterBlocks;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavBlocks;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavSimpleBlock;

class SiteFooterGrapesJsBlockTest extends TestCase
{
    /** @test */
    public function it_treats_only_prefixed_ids_as_footer_variants(): void
    {
        $this->assertFalse(SiteFooterBlocks::isFooterBlockId('site_footer'));
        $this->assertTrue(SiteFooterBlocks::isFooterBlockId('site_footer_columns_simple'));
    }

    /** @test */
    public function it_renders_footer_columns_with_menu_slots(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml(['columns' => 3], []);

        $this->assertStringContainsString('data-voodbuilder-menu="footer_col_1"', $html);
        $this->assertStringContainsString('data-voodbuilder-footer-col="4"', $html);
        $this->assertStringContainsString('hidden', $html);
    }

    /** @test */
    public function site_nav_blocks_are_registered(): void
    {
        $this->assertContains(SiteNavSimpleBlock::class, SiteNavBlocks::blockClasses());
    }
}
