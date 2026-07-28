<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsRichContentBlockAdapter;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavSimpleBlock;
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

    public function test_canvas_preview_preserves_block_identity_attributes(): void
    {
        $serverRegistry = new GrapesJsServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteNavSimpleBlock::class);

        $config = [
            'variant' => 'simple',
            'main_nav_align' => 'start',
            'sticky_nav' => 'inherit',
            'show_search' => true,
            'show_notifications' => true,
            'show_profile_menu' => true,
        ];
        $wrapped = GrapesJsRichContentBlockAdapter::wrap('site_nav_simple', $config, '<p>placeholder</p>');

        $renderer = new GrapesJsDynamicBlockRenderer(new GrapesJsDynamicBlockRegistry, $serverRegistry);
        $html = $renderer->render($wrapped, null, canvasPreview: true);

        $this->assertStringContainsString('data-voodbuilder-block="site_nav_simple"', $html);
        $this->assertStringContainsString('data-voodbuilder-config', $html);
        $this->assertStringContainsString('voodbuilder-gjs-dynamic', $html);
    }

    public function test_canvas_preview_preserves_footer_block_identity(): void
    {
        $serverRegistry = new GrapesJsServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteFooterColumnsSimpleBlock::class);

        $config = SiteFooterColumnsSimpleBlock::defaultConfig();
        $wrapped = GrapesJsRichContentBlockAdapter::wrap(
            SiteFooterColumnsSimpleBlock::getId(),
            $config,
            '<p>placeholder</p>',
        );

        $renderer = new GrapesJsDynamicBlockRenderer(new GrapesJsDynamicBlockRegistry, $serverRegistry);
        $html = $renderer->render($wrapped, null, canvasPreview: true);

        $this->assertStringContainsString(
            'data-voodbuilder-block="'.SiteFooterColumnsSimpleBlock::getId().'"',
            $html,
        );
        $this->assertStringContainsString('data-voodbuilder-config', $html);
    }

    public function test_footer_render_preserves_author_container_classes(): void
    {
        $serverRegistry = new GrapesJsServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteFooterColumnsSimpleBlock::class);

        $config = SiteFooterColumnsSimpleBlock::defaultConfig();
        $encoded = htmlspecialchars(
            json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            ENT_QUOTES,
            'UTF-8',
        );

        $saved = <<<HTML
<footer data-voodbuilder-block="site_footer_columns_simple" data-voodbuilder-config="{$encoded}" data-voodbuilder-hydrate-slots="1" class="voodbuilder-gjs-dynamic voodbuilder-gjs-footer w-full border-t border-vp-divider bg-vp-bg text-vp-text-2 body-font" role="contentinfo">
  <div class="voodbuilder-gjs-container px-5 mx-auto w-full max-w-[var(--width-vp-layout)] py-16">
    <div data-voodbuilder-brand></div>
    <div data-voodbuilder-menu="footer-col-1"></div>
    <div data-voodbuilder-footer-brand-col></div>
    <div data-voodbuilder-footer-col="1" data-voodbuilder-chrome="footer-col-1"></div>
    <div data-voodbuilder-footer-col="2" data-voodbuilder-chrome="footer-col-2"></div>
    <div data-voodbuilder-footer-col="3" data-voodbuilder-chrome="footer-col-3"></div>
    <div data-voodbuilder-footer-col="4" data-voodbuilder-chrome="footer-col-4"></div>
  </div>
</footer>
HTML;

        $renderer = new GrapesJsDynamicBlockRenderer(new GrapesJsDynamicBlockRegistry, $serverRegistry);
        $published = $renderer->render($saved, null, canvasPreview: false);
        $preview = $renderer->render($saved, null, canvasPreview: true);

        $this->assertStringContainsString('py-16', $published);
        $this->assertStringNotContainsString('py-24', $published);
        $this->assertStringNotContainsString('data-voodbuilder-block', $published);

        $this->assertStringContainsString('py-16', $preview);
        $this->assertStringContainsString('data-voodbuilder-block="site_footer_columns_simple"', $preview);
    }
}
