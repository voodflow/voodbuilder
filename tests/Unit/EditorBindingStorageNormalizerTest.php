<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingField;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingStorageNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorBindingStorageNormalizerTest extends TestCase
{
    public function test_strips_live_values_and_restores_editor_placeholders(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new StorageFakeBindingSource);

        $html = '<section>'
            .'<h1 data-voodbuilder-bind="demo.latest.title">Hello world</h1>'
            .'<a href="https://example.test/tutorial" data-voodbuilder-bind="demo.latest.url">Read</a>'
            .'<button data-voodbuilder-bind="demo.latest.url">Button</button>'
            .'<img src="https://cdn.test/hero.jpg" data-voodbuilder-bind="demo.latest.image" alt="Hero"/>'
            .'</section>';

        $normalized = (new EditorBindingStorageNormalizer($registry))->normalizeHtml($html);

        $this->assertStringContainsString('[Latest item: Title]', $normalized);
        $this->assertStringContainsString('href="#"', $normalized);
        $this->assertStringContainsString('>Button<', $normalized);
        $this->assertStringContainsString('data:image/svg+xml,', $normalized);
        $this->assertStringNotContainsString('Hello world', $normalized);
        $this->assertStringNotContainsString('https://example.test/tutorial', $normalized);
        $this->assertStringNotContainsString('https://cdn.test/hero.jpg', $normalized);
    }

    public function test_text_binding_on_cta_button_stores_placeholder_label(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new StorageFakeBindingSource);

        $html = '<a href="#" role="button" data-voodbuilder-cta="true" data-voodbuilder-bind="demo.latest.title">Hello world</a>';

        $normalized = (new EditorBindingStorageNormalizer($registry))->normalizeHtml($html);

        $this->assertStringContainsString('>[Latest item: Title]<', $normalized);
        $this->assertStringContainsString('data-voodbuilder-cta-label="[Latest item: Title]"', $normalized);
        $this->assertStringNotContainsString('Hello world', $normalized);
    }

    public function test_bind_href_resets_url_and_keeps_static_label(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new StorageFakeBindingSource);

        $html = '<a href="https://example.test/tutorial" role="button" data-voodbuilder-cta="true"'
            .' data-voodbuilder-bind-href="demo.latest.url"'
            .' data-voodbuilder-cta-label="Vai al profilo">Vai al profilo</a>';

        $normalized = (new EditorBindingStorageNormalizer($registry))->normalizeHtml($html);

        $this->assertStringContainsString('href="#"', $normalized);
        $this->assertStringContainsString('>Vai al profilo<', $normalized);
        $this->assertStringContainsString('data-voodbuilder-bind-href="demo.latest.url"', $normalized);
    }
}

final class StorageFakeBindingSource implements EditorBindingSource
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
            new BindingField('image', 'Image', BindingField::TYPE_IMAGE),
        ];
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        return null;
    }
}
