<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutReadingTypography;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutReadingTypographyTest extends TestCase
{
    public function test_resolve_defaults_to_inter_and_base(): void
    {
        $resolved = ChromeLayoutReadingTypography::resolve(null);

        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_FONT, $resolved['font']);
        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_FONT, $resolved['sidebarFont']);
        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_SIZE, $resolved['size']);
        $this->assertSame('var(--text-base)', $resolved['cssSize']);
        $this->assertStringContainsString('Inter Variable', $resolved['stack']);
        $this->assertSame([], $resolved['stylesheetUrls']);
        $this->assertSame(ChromeLayoutReadingTypography::defaultTypeScale(), $resolved['typeScale']);
        $this->assertSame('var(--text-3xl)', $resolved['cssVariables']['--vp-doc-h1-size']);
        $this->assertArrayHasKey('--vp-font-family-sidebar', $resolved['cssVariables']);
    }

    public function test_resolve_normalizes_unknown_size_and_font(): void
    {
        $layout = new ChromeLayout([
            'reading_font' => 'not-a-real-font',
            'reading_font_size' => 'huge',
            'reading_sidebar_font' => 'also-fake',
        ]);

        $resolved = ChromeLayoutReadingTypography::resolve($layout);

        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_FONT, $resolved['font']);
        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_FONT, $resolved['sidebarFont']);
        $this->assertSame(ChromeLayoutReadingTypography::DEFAULT_SIZE, $resolved['size']);
        $this->assertSame('var(--text-base)', $resolved['cssSize']);
    }

    public function test_legacy_rem_and_body_tokens_map_to_tailwind(): void
    {
        $this->assertSame('var(--text-sm)', ChromeLayoutReadingTypography::cssSizeFor('sm'));
        $this->assertSame('var(--text-base)', ChromeLayoutReadingTypography::cssSizeFor('md'));
        $this->assertSame('var(--text-lg)', ChromeLayoutReadingTypography::cssSizeFor('lg'));
        $this->assertSame('var(--text-xl)', ChromeLayoutReadingTypography::cssSizeFor('xl'));
        $this->assertSame('var(--text-xl)', ChromeLayoutReadingTypography::cssSizeFor('1.25rem'));
        $this->assertSame('var(--text-base)', ChromeLayoutReadingTypography::cssSizeFor('1em'));
        $this->assertSame('var(--text-3xl)', ChromeLayoutReadingTypography::cssSizeFor('2rem'));
        $this->assertSame('var(--text-sm)', ChromeLayoutReadingTypography::cssSizeFor('text-sm'));
    }

    public function test_normalize_save_payload_stores_tailwind_tokens(): void
    {
        $payload = ChromeLayoutReadingTypography::normalizeSavePayload([
            'font' => 'inter',
            'sidebarFont' => 'inter',
            'size' => '1.125rem',
            'typeScale' => [
                'h1' => ['size' => '2.5rem', 'weight' => '700', 'leading' => '1.25'],
                'p' => ['size' => '1.125rem', 'weight' => '400', 'leading' => '1.75'],
            ],
        ]);

        $this->assertSame('inter', $payload['reading_font']);
        $this->assertSame('inter', $payload['reading_sidebar_font']);
        $this->assertSame('lg', $payload['reading_font_size']);
        $this->assertSame('4xl', $payload['reading_type_scale']['h1']['size']);
        $this->assertSame('700', $payload['reading_type_scale']['h1']['weight']);
        $this->assertSame('lg', $payload['reading_type_scale']['p']['size']);
        $this->assertSame('xl', $payload['reading_type_scale']['h3']['size']);
        $this->assertSame('sm', $payload['reading_sidebar_type_scale']['p']['size']);
    }

    public function test_size_options_are_tailwind_labels(): void
    {
        $keys = array_keys(ChromeLayoutReadingTypography::sizeOptions());

        $this->assertContains('sm', $keys);
        $this->assertContains('base', $keys);
        $this->assertContains('2xl', $keys);
        $this->assertNotContains('1.0625rem', $keys);
        $this->assertNotContains('1em', $keys);
    }
}
