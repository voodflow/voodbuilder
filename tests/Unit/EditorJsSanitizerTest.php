<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorJsSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorJsSanitizerTest extends TestCase
{
    public function test_allows_editor_component_script_patterns(): void
    {
        $js = <<<'JS'
        var items = document.querySelectorAll('#c123');
        for (var i = 0, len = items.length; i < len; i++) {
          (function (el, props) {
            el.addEventListener('click', function () {});
          })(items[i], {"classactive":"tab-active","selectortab":"aria-controls"});
        }
        JS;

        $this->assertSame(trim($js), EditorJsSanitizer::sanitize($js));
    }

    public function test_strips_dangerous_script_patterns(): void
    {
        $this->assertSame('', EditorJsSanitizer::sanitize('eval("alert(1)")'));
        $this->assertSame('', EditorJsSanitizer::sanitize('<script>alert(1)</script>'));
        $this->assertSame('', EditorJsSanitizer::sanitize('fetch("/evil")'));
    }
}
