<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsCustomCodeSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsCustomCodeSanitizerTest extends TestCase
{
    public function test_strips_script_tags_and_inline_handlers(): void
    {
        $dirty = '<div onclick="alert(1)"><script>alert("x")</script><a href="javascript:evil()">x</a></div>';
        $clean = GrapesJsCustomCodeSanitizer::sanitize($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
    }
}
