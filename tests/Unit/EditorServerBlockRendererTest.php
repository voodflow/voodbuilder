<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Contracts\EditorServerBlock;
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

    public function test_published_render_keeps_author_background_on_anchored_inner_nodes(): void
    {
        $serverRegistry = new EditorServerBlockRegistry;
        $serverRegistry->register('Test', AnchoredCardTestBlock::class);

        $saved = '<section data-voodbuilder-block="test_anchored_card" data-voodbuilder-config="{}" class="voodbuilder-editor-dynamic">'
            . '<div data-voodbuilder-dropzone="copy" id="ihhjoig" data-vb-style-bg-opacity="0.45" data-vb-style-bg-src="/storage/4/photo.webp"'
            . ' class="rounded-2xl p-8 bg-cover bg-center bg-no-repeat bg-black"'
            . ' style="background-image:linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url(\'/storage/4/photo.webp\');background-color:transparent;">'
            . '<h2>Old title</h2></div>'
            . '<div data-voodbuilder-dropzone="plan" id="iplain"><p>Plan</p></div>'
            . '</section>';

        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $serverRegistry);
        $html = $renderer->render($saved);

        $this->assertStringContainsString('id="ihhjoig"', $html);
        $this->assertStringContainsString('data-vb-style-bg-src="/storage/4/photo.webp"', $html);
        $this->assertStringContainsString("url('/storage/4/photo.webp')", $html);
        $this->assertStringContainsString('bg-black', $html);
        $this->assertStringNotContainsString('bg-vp-bg-elv p-8 copy-fresh', $html);
        $this->assertStringContainsString('Fresh title', $html);
        $this->assertStringContainsString('id="iplain"', $html);
        $this->assertStringContainsString('class="plan-fresh"', $html);
    }
}

final class AnchoredCardTestBlock implements EditorServerBlock
{
    public static function getId(): string
    {
        return 'test_anchored_card';
    }

    public static function getLabel(): string
    {
        return 'Anchored card';
    }

    public static function defaultConfig(): array
    {
        return [];
    }

    public static function toHtml(array $config, array $context): string
    {
        return '<section class="voodbuilder-editor-section">'
            . '<div data-voodbuilder-dropzone="copy" class="rounded-2xl bg-vp-bg-elv p-8 copy-fresh"><h2>Fresh title</h2></div>'
            . '<div data-voodbuilder-dropzone="plan" class="plan-fresh"><p>Plan</p></div>'
            . '</section>';
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return self::toHtml($config, $context);
    }
}
