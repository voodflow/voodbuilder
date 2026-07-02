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
        $component = BuilderComponent::query()->create([
            'name' => 'Hero',
            'html' => '<div class="voodbuilder-pasted-component"><button class="bg-indigo-600 text-white">Go</button></div>',
            'css' => '.voodbuilder-pasted-component .bg-indigo-600 { background-color: #4f46e5; }',
        ]);

        $html = '<div data-voodbuilder-component="'.$component->id.'"></div>';

        $css = app(GrapesJsComponentCssRenderer::class)->cssForHtml($html);

        $this->assertStringContainsString('bg-vp-brand', $css);
        $this->assertStringNotContainsString('bg-indigo-600', $css);
    }
}
