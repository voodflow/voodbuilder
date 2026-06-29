<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Contracts\GrapesJsBindingSource;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingField;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingKey;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Vpress\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsBindingRendererTest extends TestCase
{
    public function test_replaces_text_and_link_bindings(): void
    {
        $registry = new BindingRegistry;
        $registry->register(new FakeLatestBindingSource);

        $html = '<section>'
            .'<h1 data-vpress-bind="demo.latest.title">Placeholder</h1>'
            .'<a href="#" data-vpress-bind="demo.latest.url">Read</a>'
            .'</section>';

        $rendered = (new GrapesJsBindingRenderer($registry))->render($html);

        $this->assertStringContainsString('>Hello world<', $rendered);
        $this->assertStringContainsString('href="https://example.test/tutorial"', $rendered);
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
        ];
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        return match ($fieldId) {
            'title' => 'Hello world',
            'url' => 'https://example.test/tutorial',
            default => null,
        };
    }
}
