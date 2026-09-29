<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorChromeHtmlPipeline;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorChromeHtmlPipelineTest extends TestCase
{
    public function test_chrome_markup_passes_the_security_sanitizer(): void
    {
        $html = '<header><a href="javascript:alert(1)" onclick="steal()">Home</a>'
            . '<script>steal()</script><nav data-voodbuilder-chrome-shell-part="nav">Menu</nav></header>';

        $rendered = EditorChromeHtmlPipeline::render($html);

        $this->assertStringNotContainsStringIgnoringCase('javascript:', $rendered);
        $this->assertStringNotContainsString('onclick', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringContainsString('data-voodbuilder-chrome-shell-part="nav"', $rendered);
    }

    public function test_theme_toggle_cta_anchors_are_restored_to_buttons(): void
    {
        $html = '<header><a type="button" data-theme-toggle href="#" data-voodbuilder-cta="true" data-voodbuilder-cta-label="Dark mode">'
            . '<span data-theme-toggle-label>Dark mode</span></a></header>';

        $rendered = EditorChromeHtmlPipeline::render($html);

        $this->assertStringNotContainsString('href="#"', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-cta', $rendered);
        $this->assertStringContainsString('<button', $rendered);
        $this->assertStringContainsString('data-theme-toggle', $rendered);
    }
}
