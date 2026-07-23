<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsRepeatRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsRepeatRendererTest extends TestCase
{
    public function test_renders_empty_state_when_list_is_empty(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-repeat="nonexistent.list">
    <article data-voodbuilder-repeat-item><h2>Item</h2></article>
    <p data-voodbuilder-repeat-empty>No articles yet.</p>
</div>
HTML;

        $rendered = app(GrapesJsRepeatRenderer::class)->render($html);

        $this->assertStringContainsString('No articles yet.', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-repeat', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-repeat-item', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-repeat-empty', $rendered);
        $this->assertStringNotContainsString('<h2>Item</h2>', $rendered);
    }

    public function test_removes_template_when_list_is_empty_without_empty_state(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-repeat="nonexistent.list">
    <article data-voodbuilder-repeat-item><h2>Item</h2></article>
</div>
HTML;

        $rendered = app(GrapesJsRepeatRenderer::class)->render($html);

        $this->assertStringNotContainsString('<h2>Item</h2>', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-repeat', $rendered);
    }

    public function test_strips_offset_attribute_after_empty_render(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-repeat="nonexistent.list" data-voodbuilder-repeat-limit="3" data-voodbuilder-repeat-offset="1">
    <article data-voodbuilder-repeat-item><h2>Item</h2></article>
</div>
HTML;

        $rendered = app(GrapesJsRepeatRenderer::class)->render($html);

        $this->assertStringNotContainsString('data-voodbuilder-repeat-offset', $rendered);
        $this->assertStringNotContainsString('<h2>Item</h2>', $rendered);
    }
}
