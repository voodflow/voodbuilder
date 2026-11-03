<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;

final class EditorRendererMediaHeroShellTest extends TestCase
{
    private function pageWithHtml(string $html): SitePage
    {
        $page = new SitePage;
        $page->builder_payload = ['html' => $html, 'css' => ''];

        return $page;
    }

    public function test_a_media_hero_payload_gets_its_content_shell_back_on_render(): void
    {
        // A legacy save stripped the shell markers off the hero's copy row.
        $html = '<section data-voodbuilder-block="vb-bg-image" data-voodbuilder-section-block="vb-bg-image" class="voodbuilder-editor-section relative overflow-hidden"><div data-voodbuilder-role="media" aria-hidden="true" class="voodbuilder-hero-media"><img src="/hero.webp" class="voodbuilder-hero-media__img"></div><div id="ifikd3" class="relative z-10 flex items-center px-5"><div class="max-w-3xl"><div data-voodbuilder-dropzone="copy"><h1>LED</h1></div></div></div></section>';

        $rendered = app(EditorRenderer::class)->html($this->pageWithHtml($html));

        $this->assertMatchesRegularExpression('/<div[^>]*id="ifikd3"[^>]*class="[^"]*voodbuilder-editor-container[^"]*"[^>]*>/', $rendered);
        $this->assertMatchesRegularExpression('/<div[^>]*id="ifikd3"[^>]*data-voodbuilder-role="content"[^>]*>/', $rendered);
        $this->assertStringContainsString('LED', $rendered);
    }

    public function test_a_dynamic_block_layout_row_is_still_unstamped_on_render(): void
    {
        $html = '<section data-voodbuilder-block="demo_pages_explorer" data-voodbuilder-config="{}" class="voodbuilder-editor-section voodbuilder-editor-dynamic"><div data-voodbuilder-role="content" data-voodbuilder-content-width="normal" class="voodbuilder-editor-container flex flex-col gap-4 sm:flex-row sm:justify-between mx-auto w-full max-w-[80rem]"><h2>Explore</h2></div></section>';

        $rendered = app(EditorRenderer::class)->html($this->pageWithHtml($html));

        $this->assertStringNotContainsString('data-voodbuilder-content-width', $rendered);
        $this->assertStringContainsString('flex flex-col gap-4 sm:flex-row sm:justify-between', $rendered);
        $this->assertStringContainsString('Explore', $rendered);
    }
}
