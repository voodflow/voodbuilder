<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\ReadingPreviewRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class ReadingPreviewRegistryTest extends TestCase
{
    public function test_default_sample_is_always_available(): void
    {
        $registry = app(ReadingPreviewRegistry::class);
        $preview = $registry->resolve('sample');

        $this->assertArrayHasKey('sample', $registry->options());
        $this->assertStringContainsString('Layout model', $preview['html']);
        $this->assertStringContainsString('--vp-font-family-doc', $preview['html']);
    }

    public function test_companions_can_register_channel_previews(): void
    {
        Voodbuilder::readingPreview('acme', [
            'label' => 'Acme Docs',
            'eyebrow' => 'Acme',
            'html' => '<h1>Acme guide</h1><p>Hello</p>',
        ]);

        $registry = app(ReadingPreviewRegistry::class);

        $this->assertArrayHasKey('acme', $registry->options());
        $this->assertSame('Acme Docs', $registry->options()['acme']);

        $preview = $registry->resolve('acme');

        $this->assertSame('Acme Docs', $preview['label']);
        $this->assertSame('Acme', $preview['eyebrow']);
        $this->assertStringContainsString('Acme guide', $preview['html']);
    }
}
