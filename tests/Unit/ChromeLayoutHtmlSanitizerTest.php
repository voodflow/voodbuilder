<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\ChromeLayoutHtmlSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutHtmlSanitizerTest extends TestCase
{
    public function test_dedupes_duplicate_footer_blocks(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-block="site_nav_simple"></div>
<div data-voodbuilder-content-slot="main"></div>
<div data-voodbuilder-block="site_footer_columns_simple"></div>
<div data-voodbuilder-block="site_footer_columns_simple"></div>
HTML;

        $normalized = ChromeLayoutHtmlSanitizer::normalizeStoredHtml($html);

        $this->assertSame(1, substr_count($normalized, 'site_footer_columns_simple'));
        $this->assertStringNotContainsString('data-voodbuilder-chrome-shell', $normalized);
    }

    public function test_hoists_footer_and_progress_nested_inside_nav(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-block="site_nav_simple" data-voodbuilder-config="{}">
  <header>nav</header>
  <footer data-voodbuilder-block="site_footer_columns_simple" data-voodbuilder-config="{}">foot</footer>
  <div class="vb-reading-progress" data-voodbuilder-progress></div>
</div>
<div data-voodbuilder-content-slot="main"></div>
HTML;

        $normalized = ChromeLayoutHtmlSanitizer::normalizeStoredHtml($html);

        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-block="site_nav_simple"[^>]*>\s*<header>nav<\/header>\s*<\/div>/s',
            $normalized,
        );
        $this->assertStringContainsString('site_footer_columns_simple', $normalized);
        $this->assertStringContainsString('data-voodbuilder-progress', $normalized);
        $this->assertSame(1, substr_count($normalized, 'site_footer_columns_simple'));
    }
}
