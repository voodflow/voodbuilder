<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRenderer;
use Voodflow\Voodbuilder\Support\Editor\EditorRichContentBlockAdapter;
use Voodflow\Voodbuilder\Support\Editor\EditorServerBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\Editor\SiteNavSimpleBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorDynamicBlockRendererTest extends TestCase
{
    public function test_renders_dynamic_block_html_from_database(): void
    {
        $registry = new EditorDynamicBlockRegistry;
        $registry->register('Voodbuilder', FeaturesGridBlock::class);

        $config = [
            'title' => 'Features',
            'features' => [
                ['title' => 'Fast', 'text' => 'Quick setup'],
            ],
        ];
        $wrapped = EditorRichContentBlockAdapter::wrap('features_grid', $config, '<p>placeholder</p>');

        $page = new SitePage([
            'slug' => 'test-page',
            'builder' => PageBuilder::Visual,
            'builder_payload' => ['html' => $wrapped],
        ]);

        $renderer = new EditorDynamicBlockRenderer($registry, new EditorServerBlockRegistry);
        $html = $renderer->render($wrapped, $page);

        $this->assertStringNotContainsString('data-voodbuilder-block', $html);
        $this->assertStringContainsString('Fast', $html);
    }

    public function test_canvas_preview_preserves_block_identity_attributes(): void
    {
        $serverRegistry = new EditorServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteNavSimpleBlock::class);

        $config = [
            'variant' => 'simple',
            'main_nav_align' => 'start',
            'sticky_nav' => 'inherit',
            'show_search' => true,
            'show_notifications' => true,
            'show_profile_menu' => true,
        ];
        $wrapped = EditorRichContentBlockAdapter::wrap('site_nav_simple', $config, '<p>placeholder</p>');

        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $serverRegistry);
        $html = $renderer->render($wrapped, null, canvasPreview: true);

        $this->assertStringContainsString('data-voodbuilder-block="site_nav_simple"', $html);
        $this->assertStringContainsString('data-voodbuilder-config', $html);
        $this->assertStringContainsString('voodbuilder-editor-dynamic', $html);
    }

    public function test_canvas_preview_preserves_footer_block_identity(): void
    {
        $serverRegistry = new EditorServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteFooterColumnsSimpleBlock::class);

        $config = SiteFooterColumnsSimpleBlock::defaultConfig();
        $wrapped = EditorRichContentBlockAdapter::wrap(
            SiteFooterColumnsSimpleBlock::getId(),
            $config,
            '<p>placeholder</p>',
        );

        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $serverRegistry);
        $html = $renderer->render($wrapped, null, canvasPreview: true);

        $this->assertStringContainsString(
            'data-voodbuilder-block="'.SiteFooterColumnsSimpleBlock::getId().'"',
            $html,
        );
        $this->assertStringContainsString('data-voodbuilder-config', $html);
    }

    public function test_footer_render_preserves_author_container_classes(): void
    {
        $serverRegistry = new EditorServerBlockRegistry;
        $serverRegistry->register('Voodbuilder', SiteFooterColumnsSimpleBlock::class);

        $config = SiteFooterColumnsSimpleBlock::defaultConfig();
        $encoded = htmlspecialchars(
            json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            ENT_QUOTES,
            'UTF-8',
        );

        $saved = <<<HTML
<footer data-voodbuilder-block="site_footer_columns_simple" data-voodbuilder-config="{$encoded}" data-voodbuilder-hydrate-slots="1" class="voodbuilder-editor-dynamic voodbuilder-editor-footer w-full border-t border-vp-divider bg-vp-bg text-vp-text-2 body-font" role="contentinfo">
  <div class="voodbuilder-editor-container px-5 mx-auto w-full max-w-[var(--width-vp-layout)] py-16">
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

        $renderer = new EditorDynamicBlockRenderer(new EditorDynamicBlockRegistry, $serverRegistry);
        $published = $renderer->render($saved, null, canvasPreview: false);
        $preview = $renderer->render($saved, null, canvasPreview: true);

        $this->assertStringContainsString('py-16', $published);
        $this->assertStringNotContainsString('py-24', $published);
        $this->assertStringNotContainsString('data-voodbuilder-block', $published);

        $this->assertStringContainsString('py-16', $preview);
        $this->assertStringContainsString('data-voodbuilder-block="site_footer_columns_simple"', $preview);
    }

    public function test_published_render_preserves_author_content_width_on_dynamic_shells(): void
    {
        $registry = new EditorDynamicBlockRegistry;
        $registry->register('Test', StubContentWidthDynamicBlock::class);

        $config = ['heading' => 'Exhibitors'];
        $encoded = htmlspecialchars(
            json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            ENT_QUOTES,
            'UTF-8',
        );

        $saved = <<<HTML
<div data-voodbuilder-block="stub_content_width" data-voodbuilder-config="{$encoded}" class="voodbuilder-editor-dynamic">
  <section class="voodbuilder-editor-section w-full">
    <div class="voodbuilder-editor-container mx-auto max-w-[80rem] px-6" data-voodbuilder-role="content" data-voodbuilder-content-width="normal" style="width: 100%; max-width: 80rem; margin-left: auto; margin-right: auto;">
      <h2>Saved</h2>
    </div>
  </section>
</div>
HTML;

        $renderer = new EditorDynamicBlockRenderer($registry, new EditorServerBlockRegistry);
        $published = $renderer->render($saved, null, canvasPreview: false);

        $this->assertStringContainsString('data-voodbuilder-content-width="normal"', $published);
        $this->assertStringContainsString('max-w-[80rem]', $published);
        $this->assertStringContainsString('max-width: 80rem', $published);
        $this->assertStringContainsString('Fresh render', $published);
        $this->assertStringNotContainsString('data-voodbuilder-block', $published);
    }

    public function test_published_render_preserves_author_root_spacing_classes_on_dynamic_blocks(): void
    {
        $registry = new EditorDynamicBlockRegistry;
        $registry->register('Test', StubContentWidthDynamicBlock::class);

        $config = ['heading' => 'Gallery'];
        $encoded = htmlspecialchars(
            json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            ENT_QUOTES,
            'UTF-8',
        );

        $saved = <<<HTML
<section data-voodbuilder-block="stub_content_width" data-voodbuilder-config="{$encoded}" class="voodbuilder-editor-dynamic mt-12 mb-8">
  <div class="voodbuilder-editor-container mx-auto px-6" data-voodbuilder-role="content">
    <h2>Saved</h2>
  </div>
</section>
HTML;

        $renderer = new EditorDynamicBlockRenderer($registry, new EditorServerBlockRegistry);
        $published = $renderer->render($saved, null, canvasPreview: false);

        $this->assertStringContainsString('mt-12', $published);
        $this->assertStringContainsString('mb-8', $published);
        $this->assertStringContainsString('Fresh render', $published);
    }
}

/**
 * @internal
 */
final class StubContentWidthDynamicBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'stub_content_width';
    }

    public static function getLabel(): string
    {
        return 'Stub';
    }

    public static function toHtml(array $config, array $data): string
    {
        return <<<'HTML'
<section class="voodbuilder-editor-section w-full">
  <div class="voodbuilder-editor-container mx-auto px-6" data-voodbuilder-role="content">
    <h2>Fresh render</h2>
  </div>
</section>
HTML;
    }
}
