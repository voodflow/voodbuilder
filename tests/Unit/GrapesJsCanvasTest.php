<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\GrapesJs\GrapesJsCanvas;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsCanvasTest extends TestCase
{
    public function test_events_sub_theme_uses_gray_canvas_background(): void
    {
        $this->assertSame('#f4f5f7', GrapesJsCanvas::pageBackgroundColor('events'));
        $this->assertStringContainsString('background-color: #f4f5f7', GrapesJsCanvas::frameStyle('events'));
    }
}
