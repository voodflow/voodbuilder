<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Vevents\Vpress\RichContent\EventDetailsBlock;
use Voodflow\Vevents\Vpress\UpcomingEventsBlock;
use Voodflow\Vexhibitors\Vpress\RichContent\ExhibitorDirectoryBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsRichContentBlockAdapter;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Vtuts\Vpress\LatestVtutsBlock;

class GrapesJsRichContentBlockAdapterTest extends TestCase
{
    public function test_wraps_block_with_dynamic_attributes(): void
    {
        $html = GrapesJsRichContentBlockAdapter::wrap('event_details', ['event_id' => 1], '<p>Details</p>');

        $this->assertStringContainsString('data-voodbuilder-block="event_details"', $html);
        $this->assertStringContainsString('data-voodbuilder-config=', $html);
        $this->assertStringContainsString('<p>Details</p>', $html);
    }

    public function test_builds_definition_from_rich_content_block(): void
    {
        $definition = GrapesJsRichContentBlockAdapter::toDefinition(
            FeaturesGridBlock::class,
            'Voodbuilder',
        );

        $this->assertSame('voodbuilder-features_grid', $definition->id);
        $this->assertStringContainsString('data-voodbuilder-block="features_grid"', $definition->content);
        $this->assertNotNull($definition->preview);
    }

    public function test_dynamic_registry_resolves_package_blocks(): void
    {
        $registry = new GrapesJsDynamicBlockRegistry;
        $registry->register('Dynamic', UpcomingEventsBlock::class);
        $registry->register('Dynamic', LatestVtutsBlock::class);
        $registry->register('Vexhibitors', ExhibitorDirectoryBlock::class);
        $registry->register('Vevents', EventDetailsBlock::class);

        $this->assertSame(UpcomingEventsBlock::class, $registry->resolve('upcoming_events'));
        $this->assertSame(LatestVtutsBlock::class, $registry->resolve('latest_vtuts'));
        $this->assertSame(ExhibitorDirectoryBlock::class, $registry->resolve('vexhibitor_directory_cta'));
        $this->assertSame(EventDetailsBlock::class, $registry->resolve('event_details'));
    }
}
