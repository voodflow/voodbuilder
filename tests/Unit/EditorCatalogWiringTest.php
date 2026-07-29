<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderSectionEditorBlocks;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorCatalogWiringTest extends TestCase
{
    public function test_section_blocks_register_when_catalog_exists(): void
    {
        if (! VoodbuilderSectionEditorBlocks::isAvailable()) {
            $this->markTestSkipped('Section catalog missing. Run php artisan voodbuilder:build-sections.');
        }

        $registry = new EditorBlockRegistry;
        VoodbuilderSectionEditorBlocks::register($registry);

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
        $this->assertStringContainsString('voodbuilder-editor-block-preview', (string) ($hero['preview'] ?? ''));
    }

    public function test_excluded_dynamic_blocks_are_not_registered_in_editor(): void
    {
        config()->set('voodbuilder.editor.excluded_editor_blocks', ['latest_vtuts']);

        $dynamic = app(EditorDynamicBlockRegistry::class);
        $registry = new EditorBlockRegistry;

        $dynamic->registerEditorBlocks($registry);

        $ids = collect($registry->toEditorBlocks())->pluck('id')->all();

        $this->assertNotContains('voodbuilder-latest_vtuts', $ids);
    }

    public function test_dynamic_registry_can_register_editor_blocks(): void
    {
        $dynamic = new EditorDynamicBlockRegistry;
        $registry = new EditorBlockRegistry;

        $dynamic->registerEditorBlocks($registry);

        $this->assertIsArray($registry->toEditorBlocks());
    }
}
