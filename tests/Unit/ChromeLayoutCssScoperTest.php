<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\ChromeLayoutCssScoper;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutCssScoperTest extends TestCase
{
    #[Test]
    public function it_scopes_utility_selectors_under_chrome_shell(): void
    {
        $scoped = ChromeLayoutCssScoper::scope('.w-full { width: 100%; }');

        $this->assertSame('[data-voodbuilder-chrome-shell] .w-full { width: 100%; }', $scoped);
    }

    #[Test]
    public function it_scopes_selectors_inside_media_queries(): void
    {
        $css = <<<'CSS'
@media (min-width: 64rem) {
  .lg\:w-1\/2 { width: 50%; }
}
CSS;

        $scoped = ChromeLayoutCssScoper::scope($css);

        $this->assertStringContainsString('@media (min-width: 64rem)', $scoped);
        $this->assertStringContainsString('[data-voodbuilder-chrome-shell] .lg\:w-1\/2 { width: 50%; }', $scoped);
    }

    #[Test]
    public function it_leaves_root_and_already_scoped_selectors_alone(): void
    {
        $css = <<<'CSS'
:root { --spacing: 0.25rem; }
[data-voodbuilder-chrome-shell] .flex { display: flex; }
CSS;

        $scoped = ChromeLayoutCssScoper::scope($css);

        $this->assertStringContainsString(':root { --spacing: 0.25rem; }', $scoped);
        $this->assertSame(1, substr_count($scoped, '[data-voodbuilder-chrome-shell]'));
        $this->assertStringContainsString('[data-voodbuilder-chrome-shell] .flex { display: flex; }', $scoped);
    }

    #[Test]
    public function it_scopes_where_selectors_without_breaking_parentheses(): void
    {
        $css = ':where(.space-y-2 > :not(:last-child)) { margin-top: 0.5rem; }';

        $scoped = ChromeLayoutCssScoper::scope($css);

        $this->assertSame(
            '[data-voodbuilder-chrome-shell] :where(.space-y-2 > :not(:last-child)) { margin-top: 0.5rem; }',
            $scoped,
        );
    }
}
