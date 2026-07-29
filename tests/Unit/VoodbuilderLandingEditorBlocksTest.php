<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingFooterBlock;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderLandingEditorBlocks;
use Voodflow\Voodbuilder\Support\VoodbuilderLandingBlocks;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderLandingEditorBlocksTest extends TestCase
{
    public function test_registers_all_landing_blocks_for_editor(): void
    {
        VoodbuilderLandingEditorBlocks::register();

        $dynamicRegistry = $this->app->make(EditorDynamicBlockRegistry::class);

        foreach (VoodbuilderLandingBlocks::blockClasses() as $blockClass) {
            $this->assertSame(
                $blockClass,
                $dynamicRegistry->resolve($blockClass::getId()),
                "Expected Editor block for {$blockClass::getId()}",
            );
            $this->assertSame(
                'Voodbuilder / Landing',
                $dynamicRegistry->categoryFor($blockClass::getId()),
            );
        }
    }

    public function test_landing_footer_block_is_exposed_to_editor_catalog(): void
    {
        VoodbuilderLandingEditorBlocks::register();

        $registry = $this->app->make(EditorBlockRegistry::class);
        $this->app->make(EditorDynamicBlockRegistry::class)->registerEditorBlocks($registry);

        $blocks = $registry->toEditorBlocks();
        $footer = collect($blocks)->firstWhere('id', 'voodbuilder-'.LandingFooterBlock::getId());

        $this->assertNotNull($footer);
        $this->assertSame('Voodbuilder / Landing', $footer['category']);
        $this->assertStringContainsString('data-voodbuilder-block="landing_footer"', $footer['content']);

        $footerVariant = collect($blocks)->firstWhere('id', 'voodbuilder-landing_footer-a');

        $this->assertNotNull($footerVariant);
        $this->assertSame('Voodbuilder / Landing Footer', $footerVariant['category']);
        $this->assertStringContainsString('"variant":"a"', html_entity_decode($footerVariant['content']));
    }
}
