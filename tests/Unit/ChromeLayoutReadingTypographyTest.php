<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutReadingTypography;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutReadingTypographyTest extends TestCase
{
    public function test_resolve_defaults_inherit_site_typography(): void
    {
        $resolved = ChromeLayoutReadingTypography::resolve(null);

        $this->assertSame('', $resolved['font']);
        $this->assertSame('', $resolved['headingFont']);
        $this->assertSame('lato', $resolved['inherited']['bodyFont']);
        $this->assertSame('montserrat', $resolved['inherited']['headingFont']);
        $this->assertSame([], $resolved['stylesheetUrls']);
        $this->assertSame('var(--vp-font-family-body, var(--font-sans))', $resolved['cssVariables']['--vp-font-family-doc']);
        $this->assertSame('var(--vp-font-family-heading, var(--font-heading))', $resolved['cssVariables']['--vp-font-family-doc-heading']);
        $this->assertSame('var(--vp-font-family-doc)', $resolved['cssVariables']['--vp-font-family-sidebar']);
        $this->assertSame('var(--vp-app-h1-weight)', $resolved['cssVariables']['--vp-doc-h1-weight']);
        $this->assertSame('var(--vp-app-h1-size)', $resolved['sizeVariables']['base']['--vp-doc-h1-size']);
        $this->assertSame([], $resolved['sizeVariables']['md']);
        $this->assertSame([], $resolved['sizeVariables']['lg']);
    }

    public function test_size_variables_are_mobile_first_overrides_of_site_sizes(): void
    {
        $layout = new ChromeLayout([
            'reading_type_scale' => [
                'h1' => ['size' => '2xl', 'sizeLg' => '5xl'],
                'h2' => ['sizeMd' => '3xl'],
            ],
        ]);

        $resolved = ChromeLayoutReadingTypography::resolve($layout);

        $this->assertSame('var(--text-2xl)', $resolved['sizeVariables']['base']['--vp-doc-h1-size']);
        $this->assertArrayNotHasKey('--vp-doc-h1-size', $resolved['sizeVariables']['md']);
        $this->assertSame('var(--text-5xl)', $resolved['sizeVariables']['lg']['--vp-doc-h1-size']);
        $this->assertSame('var(--vp-app-h2-size)', $resolved['sizeVariables']['base']['--vp-doc-h2-size']);
        $this->assertSame('var(--text-3xl)', $resolved['sizeVariables']['md']['--vp-doc-h2-size']);
        $this->assertStringContainsString('@media (min-width: 64rem)', $resolved['css']);
        $this->assertStringNotContainsString('data-voodbuilder-editor-device', $resolved['css']);
        $this->assertStringContainsString("html[data-voodbuilder-editor-device='desktop']", $resolved['editorCss']);
        $this->assertArrayNotHasKey('--vp-doc-h1-size', $resolved['cssVariables']);
    }

    public function test_column_elements_emit_vars_only_when_overridden(): void
    {
        $defaults = ChromeLayoutReadingTypography::resolve(null);

        $this->assertArrayNotHasKey('--vp-doc-h5-weight', $defaults['cssVariables']);
        $this->assertArrayNotHasKey('--vp-doc-link-size', $defaults['sizeVariables']['base']);
        $this->assertSame('xs', $defaults['inherited']['typeScale']['h5']['size']);
        $this->assertSame('sm', $defaults['inherited']['typeScale']['link']['size']);

        $resolved = ChromeLayoutReadingTypography::resolve(new ChromeLayout([
            'reading_type_scale' => [
                'h5' => ['size' => 'sm', 'weight' => '600'],
                'link' => ['sizeMd' => 'base'],
            ],
        ]));

        $this->assertSame('var(--text-sm)', $resolved['sizeVariables']['base']['--vp-doc-h5-size']);
        $this->assertSame('600', $resolved['cssVariables']['--vp-doc-h5-weight']);
        $this->assertArrayNotHasKey('--vp-doc-link-size', $resolved['sizeVariables']['base']);
        $this->assertSame('var(--text-base)', $resolved['sizeVariables']['md']['--vp-doc-link-size']);
    }

    public function test_unknown_fonts_fall_back_to_site_fonts(): void
    {
        $layout = new ChromeLayout([
            'reading_font' => 'not-a-real-font',
            'reading_heading_font' => 'also-fake',
        ]);

        $resolved = ChromeLayoutReadingTypography::resolve($layout);

        $this->assertSame('', $resolved['font']);
        $this->assertSame('', $resolved['headingFont']);
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

    public function test_normalize_save_payload_keeps_inheritance_empty(): void
    {
        $payload = ChromeLayoutReadingTypography::normalizeSavePayload([
            'font' => '',
            'headingFont' => '',
            'typeScale' => [
                'h1' => ['size' => '', 'sizeMd' => '', 'sizeLg' => '', 'weight' => '', 'leading' => ''],
            ],
        ]);

        $this->assertNull($payload['reading_font']);
        $this->assertNull($payload['reading_heading_font']);
        $this->assertNull($payload['reading_type_scale']);
    }

    public function test_normalize_save_payload_stores_tailwind_token_overrides(): void
    {
        $payload = ChromeLayoutReadingTypography::normalizeSavePayload([
            'font' => 'inter',
            'typeScale' => [
                'h1' => ['size' => '2.5rem', 'sizeLg' => 'text-6xl', 'weight' => '700'],
                'p' => ['sizeMd' => '1.125rem'],
            ],
        ]);

        $this->assertSame('inter', $payload['reading_font']);
        $this->assertSame('4xl', $payload['reading_type_scale']['h1']['size']);
        $this->assertNull($payload['reading_type_scale']['h1']['sizeMd']);
        $this->assertSame('6xl', $payload['reading_type_scale']['h1']['sizeLg']);
        $this->assertSame('700', $payload['reading_type_scale']['h1']['weight']);
        $this->assertNull($payload['reading_type_scale']['h1']['leading']);
        $this->assertSame('lg', $payload['reading_type_scale']['p']['sizeMd']);
        $this->assertNull($payload['reading_type_scale']['h3']['size']);
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
