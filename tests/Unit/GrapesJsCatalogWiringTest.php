<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderSectionGrapesJsBlocks;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsCatalogWiringTest extends TestCase
{
    public function test_section_blocks_register_when_catalog_exists(): void
    {
        if (! VoodbuilderSectionGrapesJsBlocks::isAvailable()) {
            $this->markTestSkipped('Section catalog missing. Run php artisan voodbuilder:build-sections.');
        }

        $registry = new GrapesJsBlockRegistry;
        VoodbuilderSectionGrapesJsBlocks::register($registry);

        $blocks = $registry->toEditorBlocks();

        $this->assertNotEmpty($blocks);
        $this->assertTrue(
            collect($blocks)->contains(fn (array $definition): bool => ($definition['id'] ?? '') === 'vb-hero-1'),
        );

        $categories = collect($blocks)->pluck('category')->unique();

        $this->assertTrue($categories->contains('Hero'));
        $this->assertFalse($categories->contains(fn (string $category): bool => str_starts_with($category, 'Tailblocks')));

        $hero = collect($blocks)->firstWhere('id', 'vb-hero-1');

        $this->assertIsArray($hero);
        $this->assertStringContainsString('voodbuilder-gjs-block-preview', (string) ($hero['preview'] ?? ''));
    }

    public function test_excluded_dynamic_blocks_are_not_registered_in_editor(): void
    {
        config()->set('voodbuilder.grapesjs.excluded_editor_blocks', ['latest_vtuts']);

        $dynamic = app(GrapesJsDynamicBlockRegistry::class);
        $registry = new GrapesJsBlockRegistry;

        $dynamic->registerEditorBlocks($registry);

        $ids = collect($registry->toEditorBlocks())->pluck('id')->all();

        $this->assertNotContains('voodbuilder-latest_vtuts', $ids);
    }

    public function test_dynamic_registry_can_register_editor_blocks(): void
    {
        $dynamic = new GrapesJsDynamicBlockRegistry;
        $registry = new GrapesJsBlockRegistry;

        $dynamic->registerEditorBlocks($registry);

        $this->assertIsArray($registry->toEditorBlocks());
    }
}
