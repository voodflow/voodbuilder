<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsCustomCodeSanitizer;
use Voodflow\Vpress\Tests\TestCase;

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
