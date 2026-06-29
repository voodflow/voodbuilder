<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\TailblocksGrapesJsBlocks;
use Voodflow\Voodbuilder\Tests\TestCase;

class TailblocksGrapesJsBlocksTest extends TestCase
{
    public function test_registers_tailblocks_when_catalog_exists(): void
    {
        if (! TailblocksGrapesJsBlocks::isAvailable()) {
            $this->markTestSkipped('Tailblocks catalog not built. Run php artisan voodbuilder:build-tailblocks.');
        }

        $registry = new GrapesJsBlockRegistry;

        TailblocksGrapesJsBlocks::register($registry);

        $blocks = $registry->toEditorBlocks();

        $this->assertGreaterThan(30, count($blocks));

        $ids = array_column($blocks, 'id');

        $this->assertTrue(
            collect($ids)->contains(fn (string $id): bool => str_contains($id, 'tailblocks-hero-heroa')),
        );

        $contactA = collect($blocks)->first(
            fn (array $block): bool => str_starts_with((string) ($block['id'] ?? ''), 'tailblocks-contact-contacta'),
        );

        $this->assertIsArray($contactA);
        $this->assertStringNotContainsString('· dark', (string) ($contactA['label'] ?? ''));
        $this->assertStringContainsString('bg-vp-bg-elv', (string) ($contactA['content'] ?? ''));
        $this->assertArrayHasKey('preview', $contactA);
        $this->assertStringContainsString('voodbuilder-gjs-block-preview', (string) $contactA['preview']);
    }
}
