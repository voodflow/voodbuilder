<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsCanvas;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsCanvasTest extends TestCase
{
    public function test_events_sub_theme_uses_theme_background_variables(): void
    {
        $frameStyle = GrapesJsCanvas::frameStyle('site');

        $this->assertStringContainsString('var(--color-vp-bg', $frameStyle);
        $this->assertStringContainsString('var(--color-vp-text-1', $frameStyle);
        $this->assertStringContainsString("header[role='banner'] a img", $frameStyle);
        $this->assertStringContainsString('max-height: 2.5rem', $frameStyle);
        $this->assertStringNotContainsString('.VPRichPage--landing img', $frameStyle);
    }

    #[Test]
    public function it_converts_absolute_vite_assets_to_root_relative_urls(): void
    {
        $this->assertSame(
            '/build/assets/theme.css',
            GrapesJsCanvas::toRootRelativeAssetUrl('http://localhost:8006/build/assets/theme.css'),
        );
        $this->assertSame(
            '/build/assets/theme.css?v=1',
            GrapesJsCanvas::toRootRelativeAssetUrl('https://example.test/build/assets/theme.css?v=1'),
        );
        $this->assertSame(
            '/build/assets/theme.css',
            GrapesJsCanvas::toRootRelativeAssetUrl('/build/assets/theme.css'),
        );
    }
}
