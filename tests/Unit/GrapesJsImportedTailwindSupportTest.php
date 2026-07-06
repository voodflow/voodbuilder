<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsImportedTailwindSupport;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsImportedTailwindSupportTest extends TestCase
{
    public function test_inlines_background_url_arbitrary_class_into_style(): void
    {
        $url = 'https://images.unsplash.com/photo-1615615228002-890bb61cac6e?q=80&w=1920';
        $html = '<div class="flex bg-[url(\''.$url.'\')] bg-cover bg-center bg-no-repeat"></div>';

        $normalized = GrapesJsImportedTailwindSupport::inlineBackgroundImageClasses($html);

        $this->assertStringNotContainsString('bg-[url', $normalized);
        $this->assertStringContainsString('background-image: url(', $normalized);
        $this->assertStringContainsString('images.unsplash.com/photo-1615615228002', $normalized);
        $this->assertStringContainsString('background-size: cover', $normalized);
        $this->assertStringContainsString('background-position: center', $normalized);
        $this->assertStringContainsString('background-repeat: no-repeat', $normalized);
        $this->assertStringContainsString('class="flex', $normalized);
    }

    public function test_prepare_html_inlines_background_images_before_marking_root(): void
    {
        $url = 'https://images.unsplash.com/photo-1629666451094-8908989cae90';
        $html = '<section class="bg-[url(\''.$url.'\')] bg-cover"></section>';

        $prepared = GrapesJsImportedTailwindSupport::prepareHtml($html);

        $this->assertStringNotContainsString('bg-[url', $prepared);
        $this->assertStringContainsString($url, $prepared);
        $this->assertStringContainsString('voodbuilder-pasted-component', $prepared);
    }

    public function test_prepare_html_bakes_svg_paint_into_current_color_paths(): void
    {
        $html = '<svg style="color: #f97316;" class="text-white"><path fill="currentColor" d="M0 0"/></svg>';

        $prepared = GrapesJsImportedTailwindSupport::prepareHtml($html);

        $this->assertStringContainsString('fill="#f97316"', $prepared);
        $this->assertStringContainsString('color: #f97316', $prepared);
    }

    public function test_bake_svg_paint_reads_stroke_from_style_and_child_paths(): void
    {
        $html = '<svg fill="none" stroke="currentColor"><path stroke="currentColor" d="M0 0"/></svg>';
        $styled = '<svg fill="none" stroke="currentColor" style="color: red; stroke: red"><path stroke="red" d="M0 0"/></svg>';

        $this->assertStringContainsString('stroke="red"', GrapesJsImportedTailwindSupport::bakeSvgPaintInHtml($styled));

        $fromChild = '<svg fill="none"><path stroke="#dc2626" d="M0 0"/></svg>';

        $this->assertStringContainsString('stroke="#dc2626"', GrapesJsImportedTailwindSupport::bakeSvgPaintInHtml($fromChild));
    }

    public function test_bake_svg_paint_recolors_explicit_fill_paths(): void
    {
        $html = '<svg style="color: #22c55e;"><path fill="#ef4444" d="M0 0"/><path fill="#f87171" d="M1 1"/></svg>';

        $baked = GrapesJsImportedTailwindSupport::bakeSvgPaintInHtml($html);

        $this->assertStringContainsString('fill="#22c55e"', $baked);
        $this->assertStringNotContainsString('#ef4444', $baked);
        $this->assertStringNotContainsString('#f87171', $baked);
    }

    public function test_strip_spurious_svg_baked_paint_restores_tailwind_current_color_icons(): void
    {
        $html = '<svg class="text-vp-brand-1" fill="currentColor" style="fill: #000000;stroke: #000000;color: #000000;" viewBox="0 0 20 20">'
            .'<path fill="#000000" d="M16.707 5.293"/></svg>';

        $restored = GrapesJsImportedTailwindSupport::stripSpuriousSvgBakedPaint($html);

        $this->assertStringNotContainsString('#000000', $restored);
        $this->assertStringContainsString('fill="currentColor"', $restored);
        $this->assertStringContainsString('text-vp-brand-1', $restored);
    }

    public function test_prepare_html_does_not_bake_black_paint_over_tailwind_brand_icons(): void
    {
        $html = '<svg class="text-vp-brand-1" fill="currentColor" style="color: #000000;" viewBox="0 0 20 20">'
            .'<path fill="currentColor" d="M16.707 5.293"/></svg>';

        $prepared = GrapesJsImportedTailwindSupport::prepareHtml($html);

        $this->assertStringNotContainsString('#000000', $prepared);
        $this->assertStringContainsString('fill="currentColor"', $prepared);
    }

    public function test_prepare_html_preserves_stroke_only_section_icons(): void
    {
        $html = '<span class="text-gray-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" style="fill: #000000; color: #000000; stroke: #000000;">'
            .'<path d="M5 12h14" fill="#000000" stroke="#000000"/></svg>1.2K</span>';

        $prepared = GrapesJsImportedTailwindSupport::prepareHtml($html);

        $this->assertStringNotContainsString('#000000', $prepared);
        $this->assertStringContainsString('fill="none"', $prepared);
        $this->assertStringContainsString('stroke="currentColor"', $prepared);
    }

    public function test_bake_svg_paint_skips_black_fill_on_stroke_only_icons(): void
    {
        $html = '<svg fill="none" stroke="currentColor" style="color: #000000"><path d="M0 0" fill="#000000" stroke="#000000"/></svg>';

        $baked = GrapesJsImportedTailwindSupport::bakeSvgPaintInHtml($html);

        $this->assertStringNotContainsString('fill="#000000"', $baked);
        $this->assertStringNotContainsString('fill: #000000', $baked);
    }

    public function test_parse_background_url_class_handles_quoted_and_unquoted_urls(): void
    {
        $this->assertSame(
            'https://example.com/a.jpg',
            GrapesJsImportedTailwindSupport::parseBackgroundUrlClass("bg-[url('https://example.com/a.jpg')]"),
        );
        $this->assertSame(
            'https://example.com/a.jpg',
            GrapesJsImportedTailwindSupport::parseBackgroundUrlClass('bg-[url(https://example.com/a.jpg)]'),
        );
    }
}
