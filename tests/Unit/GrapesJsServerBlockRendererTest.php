<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Enums\PageBuilder;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockRenderer;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsRichContentBlockAdapter;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\SiteFooterGrapesJsBlock;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsServerBlockRendererTest extends TestCase
{
    public function test_renders_server_block_html(): void
    {
        $richRegistry = new GrapesJsDynamicBlockRegistry;
        $serverRegistry = new GrapesJsServerBlockRegistry;
        $serverRegistry->register('Vpress', SiteFooterGrapesJsBlock::class);

        $config = SiteFooterGrapesJsBlock::defaultConfig();
        $wrapped = GrapesJsRichContentBlockAdapter::wrap(
            SiteFooterGrapesJsBlock::getId(),
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

        $this->assertStringNotContainsString('data-vpress-block', $html);
        $this->assertStringContainsString('data-vpress-gjs-site-footer', $html);
    }
}
