<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Tests\TestCase;

class SitePageLandingLayoutTest extends TestCase
{
    public function test_landing_layout_uses_landing_view_and_section(): void
    {
        $page = new SitePage([
            'layout' => 'landing',
            'hide_site_footer' => true,
            'hide_site_nav' => true,
        ]);

        $this->assertTrue($page->isLandingLayout());
        $this->assertTrue($page->shouldHideSiteFooter());
        $this->assertTrue($page->shouldHideSiteNav());
        $this->assertSame('landing', $page->contentSection());
        $this->assertStringContainsString('landing', $page->layoutView());
    }
}
