<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRenderer;
use Voodflow\Voodbuilder\Support\Editor\EditorRichContentBlockAdapter;
use Voodflow\Voodbuilder\Support\Editor\EditorServerBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorServerBlockRendererTest extends TestCase
{
    public function test_renders_server_block_html(): void
    {
        $richRegistry = new EditorDynamicBlockRegistry;
        $serverRegistry = new EditorServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteFooterColumnsSimpleBlock::class);

        $config = SiteFooterColumnsSimpleBlock::defaultConfig();
        $wrapped = EditorRichContentBlockAdapter::wrap(
            SiteFooterColumnsSimpleBlock::getId(),
            $config,
            '<p>placeholder</p>',
        );

        $page = new SitePage([
            'slug' => 'landing',
            'builder' => PageBuilder::Visual,
            'builder_payload' => ['html' => $wrapped],
        ]);

        $renderer = new EditorDynamicBlockRenderer($richRegistry, $serverRegistry);
        $html = $renderer->render($wrapped, $page);

        $this->assertStringNotContainsString('data-voodbuilder-block', $html);
        $this->assertStringContainsString('voodbuilder-editor-dynamic', $html);
        $this->assertStringContainsString('role="contentinfo"', $html);
        $this->assertStringContainsString('data-voodbuilder-footer-col', $html);
    }
}
