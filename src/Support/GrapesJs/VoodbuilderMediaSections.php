<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Generic media sections for the visual page builder (hero backgrounds, image/video sliders).
 */
final class VoodbuilderMediaSections
{
    public static function registerBlocks(): void
    {
        foreach (self::blockDefinitions() as $definition) {
            Voodbuilder::grapesJsBlock(
                $definition['id'],
                $definition['label'],
                $definition['category'],
                $definition['content'],
                ['title' => $definition['label']],
            );
        }
    }

    /**
     * @return list<array{id: string, label: string, category: string, content: string}>
     */
    public static function blockDefinitions(): array
    {
        return [
            [
                'id' => 'vb-bg-image',
                'label' => 'Background image',
                'category' => 'Hero',
                'content' => self::backgroundImage(),
            ],
            [
                'id' => 'vb-bg-video',
                'label' => 'Background video',
                'category' => 'Hero',
                'content' => self::backgroundVideo(),
            ],
            [
                'id' => 'vb-slider-images',
                'label' => 'Image slider',
                'category' => 'Gallery',
                'content' => self::sliderImages(),
            ],
            [
                'id' => 'vb-slider-videos',
                'label' => 'Video slider',
                'category' => 'Gallery',
                'content' => self::sliderVideos(),
            ],
        ];
    }

    public static function backgroundImage(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section relative overflow-hidden bg-zinc-950 text-white" data-voodbuilder-section-block="vb-bg-image" data-vb-bg-size="cover" data-vb-bg-position="center" data-vb-bg-opacity="0.55" data-vb-min-height="70vh" style="min-height:70vh;">
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media" aria-hidden="true">
    <img src="{$image}" alt="" class="voodbuilder-hero-media__img" style="opacity:0.55;object-fit:cover;object-position:center;" loading="eager" />
    <div class="voodbuilder-hero-media__shade bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30 lg:bg-gradient-to-r lg:from-zinc-950 lg:via-zinc-950/80 lg:to-transparent" data-voodbuilder-role="shade"></div>
  </div>
  <div class="voodbuilder-gjs-container relative z-10 flex items-end px-5 pb-16 pt-28 lg:items-center lg:pb-24" data-voodbuilder-role="content" data-voodbuilder-dropzone="content" style="min-height:70vh;">
    <div class="max-w-3xl">
      <div data-voodbuilder-dropzone="copy">
        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-vp-brand-2">Featured</p>
        <h1 class="mb-5 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">Tell your story on a full-bleed canvas</h1>
        <p class="max-w-xl text-lg leading-relaxed text-zinc-200">Drop headings, copy, and buttons into the content area. The background image stays behind your message in the page builder and on the live site.</p>
      </div>
      <div class="mt-8" data-voodbuilder-dropzone="actions">
        <a href="#" class="inline-flex items-center gap-2 rounded-lg bg-vp-brand-1 px-6 py-3 text-sm font-semibold text-white hover:bg-vp-brand-2">Get started</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function backgroundVideo(): string
    {
        $poster = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section relative overflow-hidden bg-zinc-950 text-white" data-voodbuilder-section-block="vb-bg-video" data-vb-video-src="" data-vb-video-poster="" data-vb-controls="0" data-vb-muted="1" data-vb-autoplay="1" data-vb-loop="1" data-vb-min-height="70vh" style="min-height:70vh;">
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media" aria-hidden="true">
    <video class="voodbuilder-hero-media__video" poster="{$poster}" muted autoplay loop playsinline></video>
    <div class="voodbuilder-hero-media__shade bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30 lg:bg-gradient-to-r lg:from-zinc-950 lg:via-zinc-950/80 lg:to-transparent" data-voodbuilder-role="shade"></div>
  </div>
  <div class="voodbuilder-gjs-container relative z-10 flex items-end px-5 pb-16 pt-28 lg:items-center lg:pb-24" data-voodbuilder-role="content" data-voodbuilder-dropzone="content" style="min-height:70vh;">
    <div class="max-w-3xl">
      <div data-voodbuilder-dropzone="copy">
        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-vp-brand-2">Showreel</p>
        <h1 class="mb-5 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">Hero with background video</h1>
        <p class="max-w-xl text-lg leading-relaxed text-zinc-200">Set the video source from the content panel. Muted autoplay keeps motion accessible while visitors read your headline.</p>
      </div>
      <div class="mt-8" data-voodbuilder-dropzone="actions">
        <a href="#" class="inline-flex items-center gap-2 text-sm font-semibold text-white underline decoration-vp-brand-1 decoration-2 underline-offset-4 hover:text-vp-brand-2">Watch the full story</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function sliderImages(): string
    {
        $slides = implode('', array_map(
            static fn (int $index): string => self::imageSlide($index),
            [0, 1, 2],
        ));

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg px-5 py-20 text-vp-text-2" data-voodbuilder-section-block="vb-slider-images">
  <div class="voodbuilder-gjs-container">
    <div class="mb-10 max-w-2xl">
      <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Gallery</p>
      <h2 class="text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">Image slider</h2>
      <p class="mt-3 text-vp-text-2">Horizontal scroll-snap gallery with previous and next controls. Use the visual builder to swap slides or bind dynamic news later.</p>
    </div>
    <div class="voodbuilder-slider relative" data-voodbuilder-slider="images" data-vb-slider-autoplay="0" data-vb-slider-interval="5000" data-vb-slider-mode="static">
      <div class="voodbuilder-slider__track">
        {$slides}
      </div>
      <div class="voodbuilder-slider__nav mt-4 flex justify-end gap-2">
        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-vp-divider bg-vp-bg-elv text-vp-text-1 hover:bg-vp-bg-alt" data-vb-slider-prev aria-label="Previous slide">‹</button>
        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-vp-divider bg-vp-bg-elv text-vp-text-1 hover:bg-vp-bg-alt" data-vb-slider-next aria-label="Next slide">›</button>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function sliderVideos(): string
    {
        $slides = implode('', array_map(
            static fn (int $index): string => self::videoSlide($index),
            [0, 1, 2],
        ));

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg-alt px-5 py-20 text-vp-text-2" data-voodbuilder-section-block="vb-slider-videos">
  <div class="voodbuilder-gjs-container">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
      <div class="max-w-2xl">
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Missions</p>
        <h2 class="text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">Video slider</h2>
        <p class="mt-3 text-vp-text-2">Mission-style cards with poster thumbnails and play affordances. Ideal for highlight reels in the page builder.</p>
      </div>
    </div>
    <div class="voodbuilder-slider relative" data-voodbuilder-slider="videos" data-vb-slider-autoplay="0" data-vb-slider-interval="6000" data-vb-video-muted="1" data-vb-video-loop="1">
      <div class="voodbuilder-slider__track">
        {$slides}
      </div>
      <div class="voodbuilder-slider__nav mt-4 flex justify-end gap-2">
        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-vp-divider bg-vp-bg-elv text-vp-text-1 hover:bg-vp-bg-alt" data-vb-slider-prev aria-label="Previous video">‹</button>
        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-vp-divider bg-vp-bg-elv text-vp-text-1 hover:bg-vp-bg-alt" data-vb-slider-next aria-label="Next video">›</button>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    private static function imageSlide(int $index): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();
        $titles = ['Launch overview', 'Team spotlight', 'Product walkthrough'];
        $title = $titles[$index] ?? 'Slide title';
        $slideNumber = $index + 1;

        return <<<HTML
        <div class="voodbuilder-slider__slide">
          <figure class="overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-elv shadow-sm">
            <img src="{$image}" alt="" class="aspect-[16/10] w-full object-cover" width="960" height="600" loading="lazy" />
            <figcaption class="border-t border-vp-divider p-5">
              <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Slide {$slideNumber}</p>
              <h3 class="mt-2 text-lg font-bold text-vp-text-1">{$title}</h3>
            </figcaption>
          </figure>
        </div>
HTML;
    }

    private static function videoSlide(int $index): string
    {
        $poster = GrapesJsPlaceholderNormalizer::neutralImageDataUri();
        $titles = ['Orbital survey', 'Surface landing', 'Deep-space relay'];
        $labels = ['Mission', 'Expedition', 'Program'];
        $title = $titles[$index] ?? 'Mission title';
        $label = $labels[$index] ?? 'Mission';

        return <<<HTML
        <div class="voodbuilder-slider__slide">
          <article class="group overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-elv shadow-sm">
            <div class="relative aspect-[4/3] overflow-hidden">
              <img src="{$poster}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" width="640" height="480" loading="lazy" />
              <button type="button" class="absolute inset-0 flex items-center justify-center bg-zinc-950/35 text-white transition hover:bg-zinc-950/45" aria-label="Play {$title}">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full border border-white/30 bg-white/15 backdrop-blur-sm">
                  <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                </span>
              </button>
            </div>
            <div class="p-5">
              <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">{$label}</p>
              <h3 class="mt-2 text-lg font-bold text-vp-text-1 group-hover:text-vp-brand-1">{$title}</h3>
              <p class="mt-2 text-sm text-vp-text-2">Replace the poster and wire a video URL when your media library is ready.</p>
            </div>
          </article>
        </div>
HTML;
    }
}
