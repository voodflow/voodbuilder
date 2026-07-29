<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\SectionItemCountAnnotator;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderSectionEditorBlocks;
use Voodflow\Voodbuilder\Tests\TestCase;

class SectionItemCountAnnotatorTest extends TestCase
{
    public function test_redundant_block_ids_are_listed(): void
    {
        $this->assertTrue(SectionItemCountAnnotator::isRedundant('vb-content-7'));
        $this->assertFalse(SectionItemCountAnnotator::isRedundant('vb-feature-7'));
        $this->assertFalse(SectionItemCountAnnotator::isRedundant('vb-content-8'));
    }

    public function test_annotate_marks_statistic_items(): void
    {
        $html = <<<'HTML'
<section class="text-gray-600">
<div class="voodbuilder-editor-container px-5 py-24">
<div class="flex flex-wrap -m-4 text-center">
<div class="p-4 sm:w-1/4 w-1/2"><h2>2.7K</h2><p>Users</p></div>
<div class="p-4 sm:w-1/4 w-1/2"><h2>1.8K</h2><p>Subscribes</p></div>
<div class="p-4 sm:w-1/4 w-1/2"><h2>35</h2><p>Downloads</p></div>
<div class="p-4 sm:w-1/4 w-1/2"><h2>4</h2><p>Products</p></div>
</div>
</div>
</section>
HTML;

        $annotated = SectionItemCountAnnotator::annotate($html, 'vb-statistic-1');

        $this->assertStringContainsString('data-vb-items-root', $annotated);
        $this->assertStringContainsString('data-vb-item-count="4"', $annotated);
        $this->assertSame(4, preg_match_all('/\sdata-vb-item(?:=""|(?=\s|>))/', $annotated));
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

        $this->assertContains('vb-content-7', $ids);
        $this->assertContains('vb-content-8', $ids);

        $method = new \ReflectionMethod(VoodbuilderSectionEditorBlocks::class, 'shouldRegisterBlock');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null, ['id' => 'vb-content-7', 'mode' => 'adaptive']));
        $this->assertTrue($method->invoke(null, ['id' => 'vb-content-8', 'mode' => 'adaptive']));
        $this->assertTrue($method->invoke(null, ['id' => 'vb-feature-7', 'mode' => 'adaptive']));
    }
}
