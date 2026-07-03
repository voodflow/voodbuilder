<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Contracts\GrapesJsBindingSource;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingField;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingKey;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsBindingRendererTest extends TestCase
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

        $rendered = (new GrapesJsBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('>Hello world<', $rendered);
        $this->assertStringContainsString('href="https://example.test/tutorial"', $rendered);
        $this->assertStringContainsString('window.location.href=', $rendered);
        $this->assertStringContainsString('https://example.test/tutorial', $rendered);
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

        $rendered = (new GrapesJsBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('src="https://example.test/cover.jpg"', $rendered);
        $this->assertStringContainsString('alt="Hello world"', $rendered);
        $this->assertStringNotContainsString('[Latest item: Cover]', $rendered);
    }
}

final class FakeLatestBindingSource implements GrapesJsBindingSource
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
        ];
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        return match ($fieldId) {
            'title' => 'Hello world',
            'url' => 'https://example.test/tutorial',
            'image' => 'https://example.test/cover.jpg',
            default => null,
        };
    }
}
