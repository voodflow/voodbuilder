<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
use Voodflow\Voodbuilder\Tests\TestCase;

class DocLayoutChromeTest extends TestCase
{
    public function test_doc_sidebar_stays_in_flow_and_does_not_cover_footer(): void
    {
        $contents = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/views/layouts/doc.blade.php',
        );

        $this->assertStringNotContainsString('fixed top-0 bottom-0', $contents);
        $this->assertStringContainsString('sticky top-[var(--spacing-vp-nav-total)]', $contents);
        $this->assertStringContainsString('vp:flex vp:items-stretch', $contents);
    }

    public function test_landing_css_keeps_full_chrome_bars_with_boxed_nav_footer_containers(): void
    {
        $contents = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/css/landing.css',
        );

        $this->assertStringContainsString(
            "[data-voodbuilder-chrome-width='full'][data-voodbuilder-page-width='full'] [data-voodbuilder-chrome-shell]",
            $contents,
        );
        $this->assertStringContainsString('--voodbuilder-chrome-layout-max, 80rem', $contents);
        $this->assertStringContainsString('padding-inline: 1.25rem', $contents);
    }
}
