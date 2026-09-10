<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutReadingTypography;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutReadingTypographyTest extends TestCase
{
    public function test_resolve_defaults_to_inter_and_large(): void
    {
        $resolved = ChromeLayoutReadingTypography::resolve(null);

        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_FONT, $resolved['font']);
        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_SIZE, $resolved['size']);
        $this->assertSame('17px', $resolved['cssSize']);
        $this->assertStringContainsString('Inter Variable', $resolved['stack']);
        $this->assertSame([], $resolved['stylesheetUrls']);
    }

    public function test_resolve_normalizes_unknown_size_and_font(): void
    {
        $layout = new ChromeLayout([
            'reading_font' => 'not-a-real-font',
            'reading_font_size' => 'huge',
        ]);

        $resolved = ChromeLayoutReadingTypography::resolve($layout);

        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_FONT, $resolved['font']);
        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_SIZE, $resolved['size']);
        $this->assertSame('17px', $resolved['cssSize']);
    }

    public function test_css_size_map(): void
    {
        $this->assertSame('15px', ChromeLayoutReadingTypography::cssSizeFor('sm'));
        $this->assertSame('16px', ChromeLayoutReadingTypography::cssSizeFor('md'));
        $this->assertSame('17px', ChromeLayoutReadingTypography::cssSizeFor('lg'));
        $this->assertSame('18px', ChromeLayoutReadingTypography::cssSizeFor('xl'));
    }

    public function test_size_options_keys(): void
    {
        $keys = array_keys(ChromeLayoutReadingTypography::sizeOptions());

        $this->assertSame(['sm', 'md', 'lg', 'xl'], $keys);
    }
}
