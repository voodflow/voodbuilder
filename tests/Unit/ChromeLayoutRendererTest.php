<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutRendererTest extends TestCase
{
    public function test_splits_html_around_content_slot(): void
    {
        $html = <<<'HTML'
<header>Top</header>
<div data-voodbuilder-content-slot="main">Placeholder</div>
<footer>Bottom</footer>
HTML;

        $split = app(ChromeLayoutRenderer::class)->splitAroundContentSlot($html);

        $this->assertStringContainsString('Top', $split['before']);
        $this->assertStringNotContainsString('Placeholder', $split['before']);
        $this->assertStringContainsString('Bottom', $split['after']);
        $this->assertStringNotContainsString('Placeholder', $split['after']);
    }

    public function test_render_includes_css_and_js_from_layout(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Docs shell',
            'slug' => 'docs-shell',
            'html' => '<header>Nav</header><div data-voodbuilder-content-slot="main"></div><footer>Foot</footer>',
            'css' => '.chrome { color: red; }',
            'js' => 'console.log("chrome");',
            'enabled' => true,
        ]);

        $rendered = app(ChromeLayoutRenderer::class)->render($layout);

        $this->assertStringContainsString('Nav', $rendered['before']);
        $this->assertStringContainsString('Foot', $rendered['after']);
        $this->assertStringContainsString('[data-voodbuilder-chrome-shell] .chrome { color: red; }', $rendered['css']);
        $this->assertSame('console.log("chrome");', $rendered['js']);
    }

    public function test_render_dedupes_repeated_chrome_css_rules(): void
    {
        $rule = '.chrome { color: red; }';
        $layout = ChromeLayout::query()->create([
            'name' => 'Duped shell',
            'slug' => 'duped-shell',
            'html' => '<header>Nav</header><div data-voodbuilder-content-slot="main"></div>',
            'css' => $rule."\n".$rule."\n".$rule,
            'js' => '',
            'enabled' => true,
        ]);

        $rendered = app(ChromeLayoutRenderer::class)->render($layout);

        $this->assertSame('[data-voodbuilder-chrome-shell] .chrome { color: red; }', $rendered['css']);
    }

    public function test_render_scopes_base_utilities_so_they_do_not_leak_to_page_content(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Utility shell',
            'slug' => 'utility-shell',
            'html' => '<header class="w-full">Nav</header><div data-voodbuilder-content-slot="main"></div>',
            'css' => ".w-full { width: 100%; }\n@media (min-width: 64rem) {\n  .lg\\:w-1\\/2 { width: 50%; }\n}",
            'js' => '',
            'enabled' => true,
        ]);

        $rendered = app(ChromeLayoutRenderer::class)->render($layout);

        $this->assertStringContainsString('[data-voodbuilder-chrome-shell] .w-full { width: 100%; }', $rendered['css']);
        $this->assertStringContainsString('[data-voodbuilder-chrome-shell] .lg\\:w-1\\/2 { width: 50%; }', $rendered['css']);
        $this->assertStringNotContainsString("\n.w-full {", "\n".$rendered['css']);
    }
}
