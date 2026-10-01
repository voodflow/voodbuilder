<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\VoodbuilderSectionEditorBlocks;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * Item-count UX is driven by declarative HTML markers in catalogs
 * (data-vb-items-root / data-vb-item / data-vb-item-count), not PHP annotation.
 */
class SectionItemCountAnnotatorTest extends TestCase
{
    public function test_redundant_block_ids_are_listed(): void
    {
        $this->assertTrue(VoodbuilderSectionEditorBlocks::isRedundant('vb-content-7'));
        $this->assertFalse(VoodbuilderSectionEditorBlocks::isRedundant('vb-feature-7'));
        $this->assertFalse(VoodbuilderSectionEditorBlocks::isRedundant('vb-content-8'));
    }

    public function test_community_catalog_ships_declarative_item_markers(): void
    {
        if (! VoodbuilderSectionEditorBlocks::isAvailable()) {
            $this->markTestSkipped('section-blocks.json missing.');
        }

        $catalog = json_decode((string) file_get_contents(VoodbuilderSectionEditorBlocks::catalogPath()), true);
        $this->assertIsArray($catalog);

        $byId = collect($catalog)
            ->filter(fn (mixed $block): bool => is_array($block) && isset($block['id']))
            ->keyBy('id');

        foreach (['vb-feature-1', 'vb-team-1'] as $id) {
            $this->assertTrue($byId->has($id), "Missing {$id} in section-blocks.json");
            $html = (string) ($byId->get($id)['content'] ?? '');
            $this->assertStringContainsString('data-vb-items-root', $html, "{$id} must declare items-root");
            $this->assertStringContainsString('data-vb-item-count', $html, "{$id} must declare item-count");
            $this->assertMatchesRegularExpression('/\sdata-vb-item(?:=""|(?=\s|>))/', $html, "{$id} must mark items");
        }
    }

    public function test_section_registration_skips_redundant_blocks(): void
    {
        if (! VoodbuilderSectionEditorBlocks::isAvailable()) {
            $this->markTestSkipped('section-blocks.json missing.');
        }

        $catalog = json_decode((string) file_get_contents(VoodbuilderSectionEditorBlocks::catalogPath()), true);
        $this->assertIsArray($catalog);

        $ids = collect($catalog)
            ->filter(fn (mixed $block): bool => is_array($block))
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $this->assertContains('vb-content-5', $ids);
        $this->assertContains('vb-hero-2', $ids);

        $method = new \ReflectionMethod(VoodbuilderSectionEditorBlocks::class, 'shouldRegisterBlock');
        $method->setAccessible(true);

        // Redundant legacy ids stay unregistered even if absent from the trimmed catalog.
        $this->assertFalse($method->invoke(null, ['id' => 'vb-content-7', 'mode' => 'adaptive']));
        $this->assertTrue($method->invoke(null, ['id' => 'vb-content-5', 'mode' => 'adaptive']));
        $this->assertTrue($method->invoke(null, ['id' => 'vb-feature-1', 'mode' => 'adaptive']));
    }
}
