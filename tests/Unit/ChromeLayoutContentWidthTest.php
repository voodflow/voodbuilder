<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutContentWidthTest extends TestCase
{
    public function test_from_layout_full(): void
    {
        $layout = new ChromeLayout(['content_width' => 'full']);

        $this->assertSame([
            'mode' => ChromeLayoutContentWidth::MODE_FULL,
            'maxWidth' => null,
        ], ChromeLayoutContentWidth::fromLayout($layout));
        $this->assertTrue(ChromeLayoutContentWidth::isFull(ChromeLayoutContentWidth::fromLayout($layout)));
        $this->assertNull(ChromeLayoutContentWidth::cssMaxWidth(ChromeLayoutContentWidth::fromLayout($layout)));
    }

    public function test_from_layout_standard(): void
    {
        $layout = new ChromeLayout(['content_width' => 'standard']);

        $resolved = ChromeLayoutContentWidth::fromLayout($layout);

        $this->assertSame(ChromeLayoutContentWidth::MODE_STANDARD, $resolved['mode']);
        $this->assertSame(ChromeLayoutContentWidth::STANDARD_MAX_WIDTH, $resolved['maxWidth']);
        $this->assertFalse(ChromeLayoutContentWidth::isFull($resolved));
        $this->assertSame('80rem', ChromeLayoutContentWidth::cssMaxWidth($resolved));
    }

    public function test_from_layout_custom_normalizes_unitless_to_rem(): void
    {
        $layout = new ChromeLayout([
            'content_width' => 'custom',
            'content_max_width' => '72',
        ]);

        $resolved = ChromeLayoutContentWidth::fromLayout($layout);

        $this->assertSame(ChromeLayoutContentWidth::MODE_CUSTOM, $resolved['mode']);
        $this->assertSame('72rem', $resolved['maxWidth']);
    }

    public function test_resolve_prefers_layout_over_page_home_full_width(): void
    {
        $layout = new ChromeLayout(['content_width' => 'standard']);
        $page = new SitePage([
            'is_home' => true,
            'layout' => 'home',
        ]);

        $this->assertTrue($page->usesFullWidthLayout());
        $this->assertSame(
            ChromeLayoutContentWidth::MODE_STANDARD,
            ChromeLayoutContentWidth::resolve($layout, $page)['mode'],
        );
    }

    public function test_resolve_falls_back_to_page_when_no_layout(): void
    {
        $page = new SitePage([
            'is_home' => false,
            'layout' => 'page',
        ]);

        $this->assertSame(
            ChromeLayoutContentWidth::MODE_STANDARD,
            ChromeLayoutContentWidth::resolve(null, $page)['mode'],
        );
    }

    public function test_normalize_max_width_rejects_invalid(): void
    {
        $this->assertNull(ChromeLayoutContentWidth::normalizeMaxWidth('calc(100% - 2rem)'));
        $this->assertSame('1200px', ChromeLayoutContentWidth::normalizeMaxWidth('1200px'));
    }

    public function test_resolve_chrome_width_defaults_to_full(): void
    {
        $layout = new ChromeLayout(['chrome_width' => null]);

        $this->assertSame(
            ChromeLayoutContentWidth::CHROME_FULL,
            ChromeLayoutContentWidth::resolveChromeWidth($layout),
        );
        $this->assertSame(
            ChromeLayoutContentWidth::CHROME_CONTENT,
            ChromeLayoutContentWidth::resolveChromeWidth(new ChromeLayout(['chrome_width' => 'content'])),
        );
    }
}
