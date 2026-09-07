<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Voodflow\Voodbuilder\Support\RuntimeSubThemeStylesheet;

class RuntimeSubThemeStylesheetTest extends TestCase
{
    public function test_it_strips_tailwind_directives_and_keeps_plain_rules(): void
    {
        $css = <<<'CSS'
@reference "tailwindcss";
@import "something.css";

html[data-voodbuilder-sub-theme='demo'] {
    --color-vp-bg: #fff;
}

html[data-voodbuilder-sub-theme='demo'] .shell {
    @apply min-h-0 bg-vp-bg;
    padding: 1rem;
}
CSS;

        $sanitized = RuntimeSubThemeStylesheet::sanitize($css);

        $this->assertStringNotContainsString('@reference', $sanitized);
        $this->assertStringNotContainsString('@import', $sanitized);
        $this->assertStringNotContainsString('@apply', $sanitized);
        $this->assertStringContainsString('--color-vp-bg: #fff', $sanitized);
        $this->assertStringContainsString('padding: 1rem', $sanitized);
    }
}
