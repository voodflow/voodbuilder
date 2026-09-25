<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\AppTypography;
use Voodflow\Voodbuilder\Tests\TestCase;

class AppTypographyTest extends TestCase
{
    public function test_resolve_defaults_to_lato_and_montserrat(): void
    {
        $resolved = AppTypography::resolve([]);

        $this->assertSame(AppTypography::DEFAULT_BODY_FONT, $resolved['bodyFont']);
        $this->assertSame(AppTypography::DEFAULT_HEADING_FONT, $resolved['headingFont']);
        $this->assertStringContainsString('Lato', $resolved['bodyStack']);
        $this->assertStringContainsString('Montserrat', $resolved['headingStack']);
        $this->assertSame('Montserrat', $resolved['headingLabel']);
        $this->assertSame('var(--text-3xl)', $resolved['sizeVariables']['base']['--vp-app-h1-size']);
        $this->assertStringContainsString('--font-sans', $resolved['canvasCss']);
        $this->assertStringContainsString(':where(h1, h2, h3, h4, h5, h6)', $resolved['canvasCss']);
        $this->assertIsArray($resolved['stylesheetUrls']);
    }

    public function test_size_variables_stay_out_of_inline_css_variables(): void
    {
        $resolved = AppTypography::resolve([]);

        $this->assertArrayNotHasKey('--vp-app-h1-size', $resolved['cssVariables']);
        $this->assertArrayHasKey('--vp-app-h1-weight', $resolved['cssVariables']);
    }

    public function test_scale_preset_emits_media_queries_and_editor_device_rules(): void
    {
        $resolved = AppTypography::resolve(['typography_scale_preset' => 'editorial']);

        $this->assertSame('var(--text-4xl)', $resolved['sizeVariables']['base']['--vp-app-h1-size']);
        $this->assertSame('var(--text-5xl)', $resolved['sizeVariables']['md']['--vp-app-h1-size']);
        $this->assertSame('var(--text-6xl)', $resolved['sizeVariables']['lg']['--vp-app-h1-size']);
        $this->assertArrayNotHasKey('--vp-app-h2-size', $resolved['sizeVariables']['md']);
        $this->assertStringContainsString('@media (min-width: 48rem)', $resolved['canvasCss']);
        $this->assertStringNotContainsString('data-voodbuilder-editor-device', $resolved['canvasCss']);
        $this->assertStringContainsString("html[data-voodbuilder-editor-device='tablet']", $resolved['editorCanvasCss']);
    }

    public function test_unknown_scale_preset_falls_back_to_standard(): void
    {
        $resolved = AppTypography::resolve(['typography_scale_preset' => 'nope']);

        $this->assertSame(AppTypography::defaultTypeScale(), $resolved['typeScale']);
    }

    public function test_scale_preset_summary_lists_pixel_steps(): void
    {
        $this->assertSame(
            'H1 30 → 36px · H2 24 → 30px · Testo 16px',
            AppTypography::scalePresetSummary('standard', 'Testo'),
        );
    }

    public function test_normalize_save_payload_falls_back_on_unknown_values(): void
    {
        $payload = AppTypography::normalizeSavePayload([
            'typography_body_font' => 'not-real',
            'typography_heading_font' => '',
            'typography_scale_preset' => 'huge',
        ]);

        $this->assertSame(AppTypography::DEFAULT_BODY_FONT, $payload['typography_body_font']);
        $this->assertSame(AppTypography::DEFAULT_HEADING_FONT, $payload['typography_heading_font']);
        $this->assertSame(AppTypography::DEFAULT_SCALE_PRESET, $payload['typography_scale_preset']);
    }
}
