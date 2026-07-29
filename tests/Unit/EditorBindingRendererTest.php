<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingField;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingKey;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorBindingRendererTest extends TestCase
{
    public function test_replaces_text_link_and_button_bindings(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<section>'
            .'<h1 data-voodbuilder-bind="demo.latest.title">Placeholder</h1>'
            .'<a href="#" data-voodbuilder-bind="demo.latest.url">Read</a>'
            .'<button type="button" data-voodbuilder-bind="demo.latest.url">Go</button>'
            .'</section>';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('>Hello world<', $rendered);
        $this->assertStringContainsString('href="https://example.test/tutorial"', $rendered);
        $this->assertStringContainsString('window.location.href=', $rendered);
        $this->assertStringContainsString('https://example.test/tutorial', $rendered);
    }

    public function test_replaces_inline_rich_text_span_bindings(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<div class="vb-rich-text" data-voodbuilder-rich-text>'
            .'<p>By <span class="voodbuilder-editor-bound" contenteditable="false" data-voodbuilder-bind="demo.latest.title">[Latest item: Title]</span>.</p>'
            .'</div>';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('>Hello world<', $rendered);
        $this->assertStringContainsString('data-voodbuilder-bind="demo.latest.title"', $rendered);
        $this->assertStringNotContainsString('[Latest item: Title]', $rendered);
    }

    public function test_hides_empty_rich_text_dynamic_tags_on_render(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<p>Hi <span class="vb-rich-text-dynamic" data-voodbuilder-bind="demo.latest.missing" data-voodbuilder-hide-when-empty="1">[Latest: Missing]</span> there</p>';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringNotContainsString('[Latest: Missing]', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-bind="demo.latest.missing"', $rendered);
        $this->assertStringContainsString('Hi  there', $rendered);
    }

    public function test_parses_dotted_source_ids(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $parsed = BindingKey::tryParse('demo.latest.title', $registry);

        $this->assertNotNull($parsed);
        $this->assertSame('demo.latest', $parsed->sourceId);
        $this->assertSame('title', $parsed->fieldId);
    }

    public function test_image_binding_uses_title_as_alt_text(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<img src="#" alt="[Latest item: Cover]" data-voodbuilder-bind="demo.latest.image">';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('src="https://example.test/cover.jpg"', $rendered);
        $this->assertStringContainsString('alt="Hello world"', $rendered);
        $this->assertStringNotContainsString('[Latest item: Cover]', $rendered);
    }

    public function test_text_binding_on_cta_button_updates_label(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<a href="#" role="button" data-voodbuilder-cta="true" data-voodbuilder-cta-label="Read more" data-voodbuilder-bind="demo.latest.title">Read more</a>';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('>Hello world<', $rendered);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Hello world"', $rendered);
    }

    public function test_bind_href_and_label_are_independent_on_cta(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<a href="#" role="button" data-voodbuilder-cta="true"'
            .' data-voodbuilder-bind="demo.latest.title"'
            .' data-voodbuilder-bind-href="demo.latest.url"'
            .' data-voodbuilder-cta-label="[Latest: Title]">[Latest: Title]</a>';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('>Hello world<', $rendered);
        $this->assertStringContainsString('href="https://example.test/tutorial"', $rendered);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Hello world"', $rendered);
    }

    public function test_url_binding_on_card_link_strips_scraped_text_nodes(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<a href="#" data-voodbuilder-bind="demo.latest.url" data-voodbuilder-cta-label="FilamentPHPTesting Filament Resources">'
            .'<img src="/thumb.jpg" alt="">'
            .'<div><span>FilamentPHP</span><h3>Testing Filament Resources</h3></div>'
            .'FilamentPHPTesting Filament Resources'
            .'</a>';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('href="https://example.test/tutorial"', $rendered);
        $this->assertStringContainsString('>Testing Filament Resources<', $rendered);
        $this->assertStringNotContainsString('FilamentPHPTesting Filament Resources', $rendered);
        $this->assertStringNotContainsString('data-voodbuilder-cta-label', $rendered);
    }

    public function test_text_binding_on_animated_counter_syncs_count_target(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<span class="vb-animated-counter"'
            .' data-voodbuilder-animated-counter="1"'
            .' data-vb-count-from="0"'
            .' data-vb-count-to="2.7"'
            .' data-vb-count-decimals="1"'
            .' data-vb-count-suffix="K"'
            .' data-vb-count-label="2.7K"'
            .' data-vb-count-source="static"'
            .' data-voodbuilder-bind="demo.latest.read_count">2.7K</span>';

        $rendered = (new EditorBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('data-vb-count-to="22"', $rendered);
        $this->assertStringContainsString('data-vb-count-source="dynamic"', $rendered);
        $this->assertStringContainsString('data-vb-count-label="22"', $rendered);
        $this->assertStringNotContainsString('data-vb-count-to="2.7"', $rendered);
        // Deferred trigger: HTML shows "from" until runtime animates.
        $this->assertMatchesRegularExpression('/data-vb-count-to="22"[^>]*>0</', $rendered);
    }
}

final class FakeLatestBindingSource implements EditorBindingSource
{
    public function id(): string
    {
        return 'demo.latest';
    }

    public function label(): string
    {
        return 'Latest item';
    }

    public function package(): string
    {
        return 'demo';
    }

    public function packageLabel(): string
    {
        return 'Demo';
    }

    public function fields(): array
    {
        return [
            new BindingField('title', 'Title', BindingField::TYPE_TEXT),
            new BindingField('url', 'URL', BindingField::TYPE_URL),
            new BindingField('image', 'Cover', BindingField::TYPE_IMAGE),
            new BindingField('read_count', 'Read count', BindingField::TYPE_TEXT),
        ];
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        return match ($fieldId) {
            'title' => 'Hello world',
            'url' => 'https://example.test/tutorial',
            'image' => 'https://example.test/cover.jpg',
            'read_count' => '22',
            default => null,
        };
    }
}
