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
}
