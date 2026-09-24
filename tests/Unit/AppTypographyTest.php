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
        $this->assertSame('var(--text-3xl)', $resolved['cssVariables']['--vp-app-h1-size']);
        $this->assertStringContainsString('--font-sans', $resolved['canvasCss']);
        $this->assertStringContainsString(':where(h1, h2, h3, h4, h5, h6)', $resolved['canvasCss']);
        $this->assertIsArray($resolved['stylesheetUrls']);
    }

    public function test_normalize_save_payload_falls_back_on_unknown_fonts(): void
    {
        $payload = AppTypography::normalizeSavePayload([
            'typography_body_font' => 'not-real',
            'typography_heading_font' => '',
            'typography_type_scale' => [
                'h1' => ['size' => '4xl', 'weight' => '700', 'leading' => '1.2'],
            ],
        ]);

        $this->assertSame(AppTypography::DEFAULT_BODY_FONT, $payload['typography_body_font']);
        $this->assertSame(AppTypography::DEFAULT_HEADING_FONT, $payload['typography_heading_font']);
        $this->assertSame('4xl', $payload['typography_type_scale']['h1']['size']);
        $this->assertSame('2xl', $payload['typography_type_scale']['h2']['size']);
    }
}
