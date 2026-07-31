<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\Editor\EditorLibraryLayoutNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorLibraryLayoutNormalizerTest extends TestCase
{
    #[Test]
    public function it_adds_tailwind_utilities_to_slider_structural_hooks(): void
    {
        $html = <<<'HTML'
<div class="voodbuilder-slider" data-voodbuilder-slider="videos">
  <div class="voodbuilder-slider__track">
    <div class="voodbuilder-slider__slide">
      <article>
        <div class="relative overflow-hidden"><img src="/x.jpg" alt=""></div>
      </article>
    </div>
  </div>
</div>
HTML;

        $normalized = EditorLibraryLayoutNormalizer::normalize($html);

        $this->assertStringContainsString('voodbuilder-slider__track flex gap-4 overflow-x-auto snap-x', $normalized);
        $this->assertStringContainsString('voodbuilder-slider__slide w-[85%] shrink-0 snap-start', $normalized);
        $this->assertStringContainsString('md:w-2/5', $normalized);
        $this->assertStringContainsString('aspect-[4/3]', $normalized);
    }

    #[Test]
    public function it_is_idempotent_when_utilities_already_present(): void
    {
        $html = <<<'HTML'
<div class="voodbuilder-slider__track flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth [scrollbar-width:none]">
  <div class="voodbuilder-slider__slide w-[85%] shrink-0 snap-start snap-always md:w-2/5">Slide</div>
</div>
HTML;

        $once = EditorLibraryLayoutNormalizer::normalize($html);
        $twice = EditorLibraryLayoutNormalizer::normalize($once);

        $this->assertSame($once, $twice);
        $this->assertSame(1, substr_count($twice, 'flex'));
        $this->assertSame(1, substr_count($twice, 'w-[85%]'));
    }

    #[Test]
    public function it_leaves_unrelated_markup_unchanged(): void
    {
        $html = '<section class="voodbuilder-editor-section"><p>Hello</p></section>';

        $this->assertSame($html, EditorLibraryLayoutNormalizer::normalize($html));
    }

    #[Test]
    public function it_rewrites_layout_container_to_tailwind_grid_cols_without_mechanic_inline_styles(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-layout="container" data-vb-layout-preset="2" data-vb-layout-tracks="minmax(0,1fr) minmax(0,1fr)" class="voodbuilder-editor-container vb-layout-row gap-4 w-full grid mx-auto max-w-[var(--width-vp-layout)]" style="maxWidth:none;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);--vb-layout-tracks:minmax(0,1fr) minmax(0,1fr);width:100%;max-width:none;">
  <div data-voodbuilder-layout="block" class="vb-layout-block min-h-16 min-w-0"></div>
  <div data-voodbuilder-layout="block" class="vb-layout-block min-h-16 min-w-0"></div>
</div>
HTML;

        $normalized = EditorLibraryLayoutNormalizer::normalize($html);

        $this->assertStringContainsString('grid-cols-2', $normalized);
        $this->assertStringContainsString('data-vb-layout-preset="2"', $normalized);
        $this->assertStringContainsString('data-vb-layout-tracks="minmax(0,1fr) minmax(0,1fr)"', $normalized);
        $this->assertStringNotContainsString('display:grid', $normalized);
        $this->assertStringNotContainsString('grid-template-columns', $normalized);
        $this->assertStringNotContainsString('--vb-layout-tracks:', $normalized);
        $this->assertStringNotContainsString('max-width:none', $normalized);
        $this->assertStringNotContainsString('width:100%', $normalized);
        $this->assertStringNotContainsString('mx-auto', $normalized);
        $this->assertStringNotContainsString('max-w-[var(--width-vp-layout)]', $normalized);
    }

    #[Test]
    public function it_maps_asymmetric_layout_preset_to_arbitrary_grid_cols(): void
    {
        $html = '<div data-voodbuilder-layout="container" data-vb-layout-preset="1-2" data-vb-layout-tracks="minmax(0,1fr) minmax(0,2fr)" class="vb-layout-row gap-8"></div>';

        $normalized = EditorLibraryLayoutNormalizer::normalize($html);

        $this->assertStringContainsString('grid-cols-[minmax(0,1fr)_minmax(0,2fr)]', $normalized);
        $this->assertStringContainsString('gap-8', $normalized);
        $this->assertStringNotContainsString('gap-4', $normalized);
    }

    #[Test]
    public function it_keeps_content_width_measure_inline_styles_on_layout_containers(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-layout="container" data-vb-layout-preset="2" data-voodbuilder-content-width="normal" data-vb-layout-tracks="minmax(0,1fr) minmax(0,1fr)" class="grid gap-4 w-full" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);width:100%;max-width:80rem;margin-left:auto;margin-right:auto;"></div>
HTML;

        $normalized = EditorLibraryLayoutNormalizer::normalize($html);

        $this->assertStringContainsString('grid-cols-2', $normalized);
        $this->assertStringNotContainsString('grid-template-columns', $normalized);
        $this->assertStringContainsString('max-width:80rem', $normalized);
        $this->assertStringContainsString('margin-left:auto', $normalized);
    }
}
