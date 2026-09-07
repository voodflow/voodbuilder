<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorHostChrome;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorHostChromeTest extends TestCase
{
    public function test_suppresses_host_chrome_when_editor_flag_is_true(): void
    {
        $this->assertTrue(EditorHostChrome::shouldSuppressHostRender(true));
    }

    public function test_critical_hide_css_targets_host_chrome_shell(): void
    {
        $css = EditorHostChrome::criticalHideCss();

        $this->assertStringContainsString('body.voodbuilder-editor-editing [data-voodbuilder-chrome-shell]', $css);
        $this->assertStringContainsString('voodbuilder-editor-boot-overlay', $css);
    }
}
