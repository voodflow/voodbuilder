<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Contracts\GrapesJsBindingSource;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingField;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Vpress\Support\GrapesJs\Bindings\GrapesJsBindingStorageNormalizer;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsBindingStorageNormalizerTest extends TestCase
{
    public function test_strips_live_values_and_restores_editor_placeholders(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new StorageFakeBindingSource);

        $html = '<section>'
            .'<h1 data-vpress-bind="demo.latest.title">Hello world</h1>'
            .'<a href="https://example.test/tutorial" data-vpress-bind="demo.latest.url">Read</a>'
            .'<button data-vpress-bind="demo.latest.url">Button</button>'
            .'<img src="https://cdn.test/hero.jpg" data-vpress-bind="demo.latest.image" alt="Hero"/>'
            .'</section>';

        $normalized = (new GrapesJsBindingStorageNormalizer($registry))->normalizeHtml($html);

        $this->assertStringContainsString('[Latest item: Title]', $normalized);
        $this->assertStringContainsString('href="#"', $normalized);
        $this->assertStringContainsString('>Button<', $normalized);
        $this->assertStringContainsString('data:image/svg+xml,', $normalized);
        $this->assertStringNotContainsString('Hello world', $normalized);
        $this->assertStringNotContainsString('https://example.test/tutorial', $normalized);
        $this->assertStringNotContainsString('https://cdn.test/hero.jpg', $normalized);
    }
}

final class StorageFakeBindingSource implements GrapesJsBindingSource
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
