<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * @deprecated Redundant with section blocks catalog (Hero, CTA, …). Do not register at runtime.
 */
final class DefaultGrapesJsBlocks
{
    public static function register(GrapesJsBlockRegistry $registry): void
    {
        $registry
            ->register(new GrapesJsBlockDefinition(
                id: 'voodbuilder-hero',
                label: 'Hero',
                category: 'Voodbuilder',
                content: <<<'HTML'
<section class="voodbuilder-gjs-section voodbuilder-gjs-hero w-full text-center">
  <div class="voodbuilder-gjs-container px-6 pt-0 pb-16">
    <h1 class="mb-4 text-4xl font-bold text-vp-text-1 md:text-5xl">Headline</h1>
    <p class="mx-auto mb-6 max-w-2xl text-lg text-vp-text-2">Supporting copy for your landing page.</p>
    <a href="#" class="inline-flex items-center rounded-lg bg-vp-brand-3 px-6 py-3 text-sm font-medium text-white no-underline transition-colors hover:bg-vp-brand-2">Call to action</a>
  </div>
</section>
HTML,
                preview: GrapesJsBlockThumbnail::forBlockId('voodbuilder-hero'),
                attributes: ['title' => 'Hero'],
            ))
            ->register(new GrapesJsBlockDefinition(
                id: 'voodbuilder-section',
                label: 'Content section',
                category: 'Voodbuilder',
                content: <<<'HTML'
<section class="voodbuilder-gjs-section bg-vp-bg py-12">
  <div class="voodbuilder-gjs-container px-6">
    <h2 class="mb-4 text-3xl font-semibold text-vp-text-1">Section title</h2>
    <p class="leading-relaxed text-vp-text-2">Add paragraphs, images, and columns inside this section.</p>
  </div>
</section>
HTML,
                preview: GrapesJsBlockThumbnail::forBlockId('voodbuilder-section'),
                attributes: ['title' => 'Content section'],
            ))
            ->register(new GrapesJsBlockDefinition(
                id: 'voodbuilder-cta-banner',
                label: 'CTA banner',
                category: 'Voodbuilder',
                content: <<<'HTML'
<section class="voodbuilder-gjs-section voodbuilder-gjs-cta bg-vp-brand-3 py-12 text-center text-white">
  <div class="voodbuilder-gjs-container px-6">
    <h2 class="mb-3 text-3xl font-semibold">Ready to get started?</h2>
    <p class="mb-5 opacity-90">Short supporting message.</p>
    <a href="#" class="inline-flex items-center rounded-lg bg-vp-bg-elv px-6 py-3 text-sm font-medium text-vp-text-1 no-underline transition-colors hover:bg-vp-bg-alt">Contact us</a>
  </div>
</section>
HTML,
                preview: GrapesJsBlockThumbnail::forBlockId('voodbuilder-cta-banner'),
                attributes: ['title' => 'CTA banner'],
            ));
    }
}
