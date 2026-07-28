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
        $this->assertStringContainsString("header[role='banner'] a:not([data-voodbuilder-brand-logo-full='1']) img.vb-brand-logo", $frameStyle);
        $this->assertStringContainsString('max-width: min(100%, 16.25rem)', $frameStyle);
        $this->assertStringNotContainsString('max-height: 2.5rem', $frameStyle);
        $this->assertStringContainsString(
            "body[data-voodbuilder-gjs-device='desktop'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-nav]",
            $frameStyle,
        );
        $this->assertStringContainsString(
            "html body[data-voodbuilder-gjs-device='mobilePortrait'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light",
            $frameStyle,
        );
        $this->assertStringContainsString(
            "html[data-vb-logo-mode='mobile'] footer .vb-brand-logo.vb-brand-logo--desktop",
            $frameStyle,
        );
        $this->assertStringContainsString('display: flex !important', $frameStyle);
        $this->assertStringNotContainsString('.VPRichPage--landing img', $frameStyle);
        // Full chrome: restore boxed measure on shells/drop-zones for any content width
        // (layout editor unwraps chrome-shell; standard+full was previously stuck at 100%).
        $this->assertStringContainsString(
            "body[data-voodbuilder-chrome-width='full'] :is(",
            $frameStyle,
        );
        $this->assertStringContainsString('[data-voodbuilder-chrome-drop-zone]', $frameStyle);
        $this->assertStringContainsString(
            '--width-vp-layout: var(--voodbuilder-page-content-max, var(--voodbuilder-chrome-layout-max, 80rem))',
            $frameStyle,
        );
        $this->assertStringContainsString('padding-inline: 1.25rem', $frameStyle);
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
