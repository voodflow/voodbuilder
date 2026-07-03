<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Voodflow\Voodbuilder\Models\BuilderComponent;
use Voodflow\Voodbuilder\Models\BuilderGlobalClass;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsElementConditionRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentCssRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsGlobalClassRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsPhaseOneRenderersTest extends TestCase
{
    use RefreshDatabase;

    public function test_condition_renderer_hides_element_for_guests(): void
    {
        $html = '<div data-voodbuilder-conditions=\'{"sets":[{"conditions":[{"key":"user_logged_in","compare":"==","value":"1"}]}]}\'>Secret</div><p>Visible</p>';

        $rendered = app(GrapesJsElementConditionRenderer::class)->render($html);

        $this->assertStringNotContainsString('Secret', $rendered);
        $this->assertStringContainsString('Visible', $rendered);
    }

    public function test_component_renderer_expands_instance_with_props(): void
    {
        $component = BuilderComponent::query()->create([
            'name' => 'Card',
            'html' => '<div class="card"><h2 data-voodbuilder-prop="title">Default</h2></div>',
            'properties' => [
                ['id' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Default'],
            ],
        ]);

        $html = '<div data-voodbuilder-component="'.$component->id.'" data-voodbuilder-component-props=\'{"title":"Hello"}\'></div>';

        $rendered = app(GrapesJsComponentRenderer::class)->render($html);

        $this->assertStringContainsString('Hello', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-component', $rendered);
        $this->assertStringContainsString('data-vb-component-id="'.$component->id.'"', $rendered);
    }

    public function test_component_renderer_preserves_inline_instance_overrides(): void
    {
        $component = BuilderComponent::query()->create([
            'name' => 'Card',
            'html' => '<div class="card"><h2>Catalog default</h2></div>',
            'properties' => [],
        ]);

        $html = '<div class="voodbuilder-gjs-component-instance" data-voodbuilder-component="'.$component->id.'">'
            .'<div class="voodbuilder-pasted-component"><h2>Page override</h2></div>'
            .'</div>';

        $rendered = app(GrapesJsComponentRenderer::class)->render($html);

        $this->assertStringContainsString('Page override', $rendered);
        $this->assertStringNotContainsString('Catalog default', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-component', $rendered);
        $this->assertStringContainsString('data-vb-component-id="'.$component->id.'"', $rendered);
        $this->assertStringNotContainsString('voodbuilder-gjs-component-instance', $rendered);
        $this->assertStringContainsString('voodbuilder-component-rendered', $rendered);
    }

    public function test_global_class_renderer_concatenates_css(): void
    {
        BuilderGlobalClass::query()->create([
            'name' => 'btn-primary',
            'label' => 'Primary button',
            'css' => '.btn-primary { color: red; }',
        ]);

        $css = app(GrapesJsGlobalClassRenderer::class)->css();

        $this->assertStringContainsString('.btn-primary', $css);
    }

    public function test_component_css_renderer_collects_styles_for_used_instances(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><button class="bg-vp-brand-3 text-white">Go</button></div>';
        $component = BuilderComponent::query()->create([
            'name' => 'Hero',
            'html' => $html,
            'css' => '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }',
            'html_checksum' => GrapesJsPastedComponentNormalizer::htmlChecksum($html),
        ]);

        $pageHtml = '<div data-voodbuilder-component="'.$component->id.'"></div>';

        $css = app(GrapesJsComponentCssRenderer::class)->cssForHtml($pageHtml);

        $this->assertStringContainsString('[data-vb-component-id="'.$component->id.'"]', $css);
        $this->assertStringContainsString('bg-vp-brand-3', $css);
        $this->assertStringNotContainsString('bg-indigo-600', $css);
    }

    public function test_component_css_renderer_uses_stored_css_without_runtime_compilation(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="mx-auto w-2/5 bg-vp-brand-3">Box</div></div>';
        $storedCss = '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';
        $component = BuilderComponent::query()->create([
            'name' => 'Box',
            'html' => $html,
            'css' => $storedCss,
            'html_checksum' => GrapesJsPastedComponentNormalizer::htmlChecksum($html),
        ]);

        $pageHtml = '<div data-voodbuilder-component="'.$component->id.'"></div>';
        $css = app(GrapesJsComponentCssRenderer::class)->cssForHtml($pageHtml);

        $this->assertStringContainsString('bg-vp-brand-3', $css);
        $this->assertStringNotContainsString('.mx-auto', $css);
        $this->assertStringNotContainsString('.w-2\\/5', $css);
    }

    public function test_page_save_sync_compiles_instance_html_without_theme_html_migration(): void
    {
        $instanceHtml = '<div class="voodbuilder-pasted-component"><div class="rounded-bl-[20px] bg-blue-200">Card</div></div>';
        $storedCss = '.voodbuilder-pasted-component .rounded-bl-\\[20px\\] { border-bottom-left-radius: 20px; }';
        $component = BuilderComponent::query()->create([
            'name' => 'Testimonials',
            'html' => '<div class="voodbuilder-pasted-component"><div class="rounded-bl-[20px]">Card</div></div>',
            'css' => $storedCss,
            'html_checksum' => GrapesJsPastedComponentNormalizer::htmlChecksum(
                '<div class="voodbuilder-pasted-component"><div class="rounded-bl-[20px]">Card</div></div>',
            ),
        ]);

        $pageHtml = '<div data-voodbuilder-component="'.$component->id.'">'.$instanceHtml.'</div>';

        app(\Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentCssLibrarySync::class)->syncFromPageHtml($pageHtml);

        $component->refresh();

        $this->assertStringContainsString('bg-blue-200', (string) $component->css);
    }

    public function test_component_css_renderer_does_not_compile_on_render(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="bg-vp-brand-3">Box</div></div>';
        $storedCss = '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';
        $component = BuilderComponent::query()->create([
            'name' => 'Box',
            'html' => $html,
            'css' => $storedCss,
            'html_checksum' => GrapesJsPastedComponentNormalizer::htmlChecksum($html),
        ]);

        $pageHtml = '<div data-voodbuilder-component="'.$component->id.'">'
            .'<div class="voodbuilder-pasted-component"><div class="mx-auto bg-vp-brand-3 bg-blue-200">Box</div></div>'
            .'</div>';

        $start = microtime(true);
        $css = app(GrapesJsComponentCssRenderer::class)->cssForHtml($pageHtml);
        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertStringContainsString('bg-vp-brand-3', $css);
        $this->assertStringNotContainsString('bg-blue-200', $css);
        $this->assertLessThan(200, $elapsedMs);
    }
}
