<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Filament\RichContent\Landing\LandingFooterBlock;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\VpressLandingGrapesJsBlocks;
use Voodflow\Vpress\Support\VpressLandingBlocks;
use Voodflow\Vpress\Tests\TestCase;

class VpressLandingGrapesJsBlocksTest extends TestCase
{
    public function test_registers_all_landing_blocks_for_grapesjs(): void
    {
        VpressLandingGrapesJsBlocks::register();

        $dynamicRegistry = $this->app->make(GrapesJsDynamicBlockRegistry::class);

        foreach (VpressLandingBlocks::blockClasses() as $blockClass) {
            $this->assertSame(
                $blockClass,
                $dynamicRegistry->resolve($blockClass::getId()),
                "Expected GrapesJS block for {$blockClass::getId()}",
            );
            $this->assertSame(
                'Vpress / Landing',
                $dynamicRegistry->categoryFor($blockClass::getId()),
            );
        }
    }

    public function test_landing_footer_block_is_exposed_to_editor_catalog(): void
    {
        VpressLandingGrapesJsBlocks::register();

        $registry = new GrapesJsBlockRegistry;
        $this->app->make(GrapesJsDynamicBlockRegistry::class)->registerEditorBlocks($registry);

        $blocks = $registry->toEditorBlocks();
        $footer = collect($blocks)->firstWhere('id', 'vpress-'.LandingFooterBlock::getId());

        $this->assertNotNull($footer);
        $this->assertSame('Vpress / Landing', $footer['category']);
        $this->assertStringContainsString('data-vpress-block="landing_footer"', $footer['content']);
    }
}
