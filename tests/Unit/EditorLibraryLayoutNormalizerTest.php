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
}
