<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\ProfileMenuLinkRegistry;
use Voodflow\Vpress\Tests\TestCase;

class ProfileMenuLinkRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        ProfileMenuLinkRegistry::flush();

        parent::tearDown();
    }

    public function test_it_returns_registered_links(): void
    {
        ProfileMenuLinkRegistry::register(fn (): array => [
            'label' => 'Exhibitor',
            'url' => '/exhibitor',
        ]);

        $links = ProfileMenuLinkRegistry::links();

        $this->assertCount(1, $links);
        $this->assertSame('Exhibitor', $links[0]['label']);
        $this->assertSame('/exhibitor', $links[0]['url']);
    }

    public function test_it_skips_null_or_invalid_resolvers(): void
    {
        ProfileMenuLinkRegistry::register(fn (): ?array => null);
        ProfileMenuLinkRegistry::register(fn (): array => ['label' => '', 'url' => '/x']);
        ProfileMenuLinkRegistry::register(function (): array {
            throw new \RuntimeException('boom');
        });

        $this->assertSame([], ProfileMenuLinkRegistry::links());
    }
}
