<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorRichContentBlockAdapter;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorRichContentBlockAdapterTest extends TestCase
{
    public function test_wraps_block_with_dynamic_attributes(): void
    {
        $html = EditorRichContentBlockAdapter::wrap('event_details', ['event_id' => 1], '<p>Details</p>');

        $this->assertStringContainsString('data-voodbuilder-block="event_details"', $html);
        $this->assertStringContainsString('data-voodbuilder-config=', $html);
        $this->assertStringContainsString('<p>Details</p>', $html);
        $this->assertStringContainsString('<div data-voodbuilder-block="event_details"', $html);
    }

    public function test_stamps_dynamic_attrs_on_single_section_root(): void
    {
        $inner = '<section class="voodbuilder-editor-section ve-demo"><div>Body</div></section>';
        $html = EditorRichContentBlockAdapter::wrap('event_testimonials', ['heading' => 'Say'], $inner);

        $this->assertStringContainsString('<section class="voodbuilder-editor-section ve-demo voodbuilder-editor-dynamic"', $html);
        $this->assertStringContainsString('data-voodbuilder-block="event_testimonials"', $html);
        $this->assertStringNotContainsString('<div data-voodbuilder-block=', $html);
        $this->assertStringContainsString('<div>Body</div>', $html);
    }

    public function test_builds_definition_from_rich_content_block(): void
    {
        $definition = EditorRichContentBlockAdapter::toDefinition(
            FeaturesGridBlock::class,
            'Voodbuilder',
        );

        $this->assertSame('voodbuilder-features_grid', $definition->id);
        $this->assertStringContainsString('data-voodbuilder-block="features_grid"', $definition->content);
        $this->assertNotNull($definition->preview);
    }

    public function test_dynamic_registry_resolves_package_blocks(): void
    {
        $registry = new EditorDynamicBlockRegistry;
        $registry->register('Voodbuilder', FeaturesGridBlock::class);

        $this->assertSame(FeaturesGridBlock::class, $registry->resolve('features_grid'));
    }
}
