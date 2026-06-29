<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsRichContentBlockAdapter;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsDynamicBlockRendererTest extends TestCase
{
    public function test_renders_dynamic_block_html_from_database(): void
    {
        $registry = new GrapesJsDynamicBlockRegistry;
        $registry->register('Voodbuilder', FeaturesGridBlock::class);

        $config = [
            'title' => 'Features',
            'features' => [
                ['title' => 'Fast', 'text' => 'Quick setup'],
            ],
        ];
        $wrapped = GrapesJsRichContentBlockAdapter::wrap('features_grid', $config, '<p>placeholder</p>');

        $page = new SitePage([
            'slug' => 'test-page',
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => ['html' => $wrapped],
        ]);

        $renderer = new GrapesJsDynamicBlockRenderer($registry, new GrapesJsServerBlockRegistry);
        $html = $renderer->render($wrapped, $page);

        $this->assertStringNotContainsString('data-voodbuilder-block', $html);
        $this->assertStringContainsString('Fast', $html);
    }
}
