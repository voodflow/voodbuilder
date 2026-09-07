<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorCustomCodeSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorCustomCodeSanitizerTest extends TestCase
{
    public function test_strips_script_tags_and_inline_handlers(): void
    {
        $dirty = '<div onclick="alert(1)"><script>alert("x")</script><a href="javascript:evil()">x</a></div>';
        $clean = EditorCustomCodeSanitizer::sanitize($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
    }
}
