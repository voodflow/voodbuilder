<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockDefinition;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageEditorTest extends TestCase
{
    public function test_editor_builder_renders_html_and_css_payload(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => '<section class="hero">Hello Grapes</section>',
                'css' => '.hero { color: red; }',
                'project' => ['pages' => []],
            ],
        ]);

        $this->assertTrue($page->usesEditorBuilder());
        $this->assertSame('<section class="hero voodbuilder-editor-section">Hello Grapes</section>', $page->renderedContent());
        $this->assertSame('.hero { color: red; }', $page->renderedStyles());
        $this->assertNull($page->renderedScripts());
    }

    public function test_editor_builder_renders_saved_component_scripts(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::Visual,
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

        $this->assertFalse($page->usesEditorBuilder());
        $this->assertSame('', $page->renderedContent());
        $this->assertNull($page->renderedStyles());
    }

    public function test_rendered_styles_are_memoized_per_request(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => '<section class="hero">Hello Grapes</section>',
                'css' => '.hero { color: red; }',
            ],
        ]);

        $first = $page->renderedStyles();
        $second = $page->renderedStyles();

        $this->assertSame($first, $second);
        $this->assertSame('.hero { color: red; }', $first);
    }

    public function test_editor_block_registry_exposes_editor_blocks(): void
    {
        $registry = new EditorBlockRegistry;

        $registry->register(new EditorBlockDefinition(
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

    public function test_editor_renderer_returns_empty_html_when_payload_missing(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::Visual,
            'builder_payload' => null,
        ]);

        $renderer = new EditorRenderer;

        $this->assertSame('', $renderer->render($page));
        $this->assertNull($renderer->css($page));
        $this->assertNull($renderer->js($page));
    }

    public function test_editor_renderer_css_repairs_corrupted_page_styles_in_package(): void
    {
        $page = new SitePage([
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => '<section class="bg-blue-200 p-4">Hi</section>',
                'css' => '.bg-blue-200 { background-color: var( }',
            ],
        ]);

        $css = app(EditorRenderer::class)->css($page);

        $this->assertIsString($css);
        $this->assertStringNotContainsString('background-color: var( }', $css);
        $this->assertStringContainsString('var(--color-blue-200,', $css);
    }
}
