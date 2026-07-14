<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsRichContentBlockAdapter;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsServerBlockRendererTest extends TestCase
{
    public function test_renders_server_block_html(): void
    {
        $richRegistry = new GrapesJsDynamicBlockRegistry;
        $serverRegistry = new GrapesJsServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteFooterColumnsSimpleBlock::class);

        $config = SiteFooterColumnsSimpleBlock::defaultConfig();
        $wrapped = GrapesJsRichContentBlockAdapter::wrap(
            SiteFooterColumnsSimpleBlock::getId(),
            $config,
            '<p>placeholder</p>',
        );

        $page = new SitePage([
            'slug' => 'landing',
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => ['html' => $wrapped],
        ]);

        $renderer = new GrapesJsDynamicBlockRenderer($richRegistry, $serverRegistry);
        $html = $renderer->render($wrapped, $page);

        $this->assertStringNotContainsString('data-voodbuilder-block', $html);
        $this->assertStringContainsString('voodbuilder-gjs-footer', $html);
    }
}
