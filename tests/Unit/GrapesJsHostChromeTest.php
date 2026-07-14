<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHostChrome;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsHostChromeTest extends TestCase
{
    public function test_suppresses_host_chrome_when_editor_flag_is_true(): void
    {
        $this->assertTrue(GrapesJsHostChrome::shouldSuppressHostRender(true));
    }

    public function test_critical_hide_css_targets_host_chrome_shell(): void
    {
        $css = GrapesJsHostChrome::criticalHideCss();

        $this->assertStringContainsString('body.voodbuilder-grapesjs-editing [data-voodbuilder-chrome-shell]', $css);
        $this->assertStringContainsString('voodbuilder-gjs-boot-overlay', $css);
    }
}
