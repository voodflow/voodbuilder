<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsCanvas;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsCanvasTest extends TestCase
{
    public function test_events_sub_theme_uses_theme_background_variables(): void
    {
        $frameStyle = GrapesJsCanvas::frameStyle('site');

        $this->assertStringContainsString('var(--color-vp-bg', $frameStyle);
        $this->assertStringContainsString('var(--color-vp-text-1', $frameStyle);
    }
}
