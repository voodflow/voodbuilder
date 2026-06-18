<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\TailblocksGrapesJsBlocks;
use Voodflow\Vpress\Tests\TestCase;

class TailblocksGrapesJsBlocksTest extends TestCase
{
    public function test_registers_tailblocks_when_catalog_exists(): void
    {
        if (! TailblocksGrapesJsBlocks::isAvailable()) {
            $this->markTestSkipped('Tailblocks catalog not built. Run php artisan vpress:build-tailblocks.');
        }

        $registry = new GrapesJsBlockRegistry;

        TailblocksGrapesJsBlocks::register($registry);

        $blocks = $registry->toEditorBlocks();

        $this->assertGreaterThan(60, count($blocks));

        $ids = array_column($blocks, 'id');

        $this->assertTrue(
            collect($ids)->contains(fn (string $id): bool => str_contains($id, 'tailblocks-hero-heroa-light')),
        );

        $contactA = collect($blocks)->firstWhere('id', 'tailblocks-contact-contacta-light');

        $this->assertIsArray($contactA);
        $this->assertSame('Contact A · light', $contactA['label']);
        $this->assertArrayHasKey('preview', $contactA);
        $this->assertStringContainsString('vpress-gjs-block-preview', (string) $contactA['preview']);
    }
}
