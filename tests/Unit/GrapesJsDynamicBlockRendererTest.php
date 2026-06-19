<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Enums\PageBuilder;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockRenderer;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsRichContentBlockAdapter;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsDynamicBlockRendererTest extends TestCase
{
    public function test_renders_dynamic_block_html_from_database(): void
    {
        $registry = new GrapesJsDynamicBlockRegistry;
        $registry->register('Vpress', FeaturesGridBlock::class);

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

        $renderer = new GrapesJsDynamicBlockRenderer($registry);
        $html = $renderer->render($wrapped, $page);

        $this->assertStringNotContainsString('data-vpress-block', $html);
        $this->assertStringContainsString('Fast', $html);
    }
}
