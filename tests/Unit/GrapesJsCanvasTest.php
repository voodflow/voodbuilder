<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\GrapesJs\GrapesJsCanvas;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsCanvasTest extends TestCase
{
    public function test_events_sub_theme_uses_theme_background_variables(): void
    {
        $frameStyle = GrapesJsCanvas::frameStyle('site');

        $this->assertStringContainsString('var(--color-vp-bg', $frameStyle);
        $this->assertStringContainsString('var(--color-vp-text-1', $frameStyle);
    }
}
