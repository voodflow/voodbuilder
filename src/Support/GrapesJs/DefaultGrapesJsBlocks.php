<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

final class DefaultGrapesJsBlocks
{
    public static function register(GrapesJsBlockRegistry $registry): void
    {
        $registry
            ->register(new GrapesJsBlockDefinition(
                id: 'vpress-hero',
                label: 'Hero',
                category: 'Vpress',
                content: <<<'HTML'
<section class="vpress-gjs-hero" style="padding: 4rem 1.5rem; text-align: center;">
  <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">Headline</h1>
  <p style="font-size: 1.125rem; opacity: 0.85; max-width: 42rem; margin: 0 auto 1.5rem;">Supporting copy for your landing page.</p>
  <a href="#" style="display: inline-block; padding: 0.75rem 1.5rem; border-radius: 0.5rem; background: #111827; color: #fff; text-decoration: none;">Call to action</a>
</section>
HTML,
                preview: GrapesJsBlockThumbnail::forBlockId('vpress-hero'),
                attributes: ['title' => 'Hero'],
            ))
            ->register(new GrapesJsBlockDefinition(
                id: 'vpress-section',
                label: 'Content section',
                category: 'Vpress',
                content: <<<'HTML'
<section class="vpress-gjs-section" style="padding: 3rem 1.5rem; max-width: 72rem; margin: 0 auto;">
  <h2 style="font-size: 1.875rem; font-weight: 600; margin-bottom: 1rem;">Section title</h2>
  <p style="line-height: 1.7;">Add paragraphs, images, and columns inside this section.</p>
</section>
HTML,
                preview: GrapesJsBlockThumbnail::forBlockId('vpress-section'),
                attributes: ['title' => 'Content section'],
            ))
            ->register(new GrapesJsBlockDefinition(
                id: 'vpress-cta-banner',
                label: 'CTA banner',
                category: 'Vpress',
                content: <<<'HTML'
<section class="vpress-gjs-cta" style="padding: 3rem 1.5rem; background: #111827; color: #fff; text-align: center;">
  <h2 style="font-size: 1.75rem; font-weight: 600; margin-bottom: 0.75rem;">Ready to get started?</h2>
  <p style="margin-bottom: 1.25rem; opacity: 0.9;">Short supporting message.</p>
  <a href="#" style="display: inline-block; padding: 0.75rem 1.5rem; border-radius: 0.5rem; background: #fff; color: #111827; text-decoration: none;">Contact us</a>
</section>
HTML,
                preview: GrapesJsBlockThumbnail::forBlockId('vpress-cta-banner'),
                attributes: ['title' => 'CTA banner'],
            ));
    }
}
