<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Tests\TestCase;

class SitePageLandingLayoutTest extends TestCase
{
    public function test_landing_layout_uses_full_width_view_and_section(): void
    {
        $page = new SitePage([
            'layout' => 'landing',
            'hide_site_footer' => true,
            'hide_site_nav' => true,
        ]);

        $this->assertTrue($page->usesFullWidthLayout());
        $this->assertTrue($page->isLandingLayout());
        $this->assertTrue($page->shouldHideSiteFooter());
        $this->assertTrue($page->shouldHideSiteNav());
        $this->assertSame('full_width', $page->contentSection());
        $this->assertStringContainsString('full-width', $page->layoutView());
    }

    public function test_home_layout_maps_to_full_width_shell(): void
    {
        $page = new SitePage([
            'layout' => 'home',
        ]);

        $this->assertTrue($page->usesFullWidthLayout());
        $this->assertSame('full_width', $page->contentSection());
        $this->assertStringContainsString('full-width', $page->layoutView());
    }
}
