<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockDefinition;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageGrapesJsTest extends TestCase
{
    public function test_grapesjs_builder_renders_html_and_css_payload(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => '<section class="hero">Hello Grapes</section>',
                'css' => '.hero { color: red; }',
                'project' => ['pages' => []],
            ],
        ]);

        $this->assertTrue($page->usesGrapesJsBuilder());
        $this->assertSame('<section class="hero">Hello Grapes</section>', $page->renderedContent());
        $this->assertSame('.hero { color: red; }', $page->renderedStyles());
        $this->assertNull($page->renderedScripts());
    }

    public function test_grapesjs_builder_renders_saved_component_scripts(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => '<div role="tablist"></div>',
                'css' => '',
                'js' => 'var items = document.querySelectorAll("#tabs");',
            ],
        ]);

        $this->assertSame('var items = document.querySelectorAll("#tabs");', $page->renderedScripts());
    }

    public function test_rich_editor_builder_keeps_tip_tap_renderer_path(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::RichEditor,
            'content' => null,
        ]);

        $this->assertFalse($page->usesGrapesJsBuilder());
        $this->assertSame('', $page->renderedContent());
        $this->assertNull($page->renderedStyles());
    }

    public function test_grapesjs_block_registry_exposes_editor_blocks(): void
    {
        $registry = new GrapesJsBlockRegistry;

        $registry->register(new GrapesJsBlockDefinition(
            id: 'demo',
            label: 'Demo',
            category: 'Test',
            content: '<div>Demo</div>',
        ));

        $this->assertSame([
            [
                'id' => 'demo',
                'label' => 'Demo',
                'category' => 'Test',
                'content' => '<div>Demo</div>',
            ],
        ], $registry->toEditorBlocks());
    }

    public function test_grapesjs_renderer_returns_empty_html_when_payload_missing(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => null,
        ]);

        $renderer = new GrapesJsRenderer;

        $this->assertSame('', $renderer->render($page));
        $this->assertNull($renderer->css($page));
        $this->assertNull($renderer->js($page));
    }
}
