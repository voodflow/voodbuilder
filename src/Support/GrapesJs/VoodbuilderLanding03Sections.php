<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Cinematic homepage sections (Landing 03) (Explorer-1 layout cues + theme tokens).
 *
 * @see https://www.jpl.nasa.gov/
 * @see https://github.com/nasa-jpl/explorer-1
 */
final class VoodbuilderLanding03Sections
{
    public const TEMPLATE_NAME = 'Landing 03';

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
                'id' => 'vb-nasa-hero',
                'label' => 'Cinematic hero',
                'category' => 'Hero',
                'content' => self::hero(),
            ],
            [
                'id' => 'vb-nasa-stats',
                'label' => 'Stats band',
                'category' => 'Stats',
                'content' => self::stats(),
            ],
            [
                'id' => 'vb-nasa-featured',
                'label' => 'Featured stories',
                'category' => 'Articles',
                'content' => self::featured(),
            ],
            [
                'id' => 'vb-nasa-teaser',
                'label' => 'Teaser with card',
                'category' => 'Content',
                'content' => self::teaser(),
            ],
            [
                'id' => 'vb-nasa-spotlight',
                'label' => 'Spotlight split',
                'category' => 'Content',
                'content' => self::spotlight(),
            ],
            [
                'id' => 'vb-nasa-missions',
                'label' => 'Mission cards',
                'category' => 'Gallery',
                'content' => self::missions(),
            ],
            [
                'id' => 'vb-nasa-explore',
                'label' => 'Explore more',
                'category' => 'Articles',
                'content' => self::explore(),
            ],
        ];
    }

    public static function pageHtml(): string
    {
        return self::hero()
            .self::stats()
            .self::featured()
            .self::teaser()
            .self::spotlight()
            .self::missions()
            .self::explore();
    }

    public static function hero(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section relative overflow-hidden bg-zinc-950 text-white" data-voodbuilder-section-block="vb-nasa-hero" style="min-height:70vh;">
  <div class="voodbuilder-hero-media" aria-hidden="true">
    <img src="{$image}" alt="" class="voodbuilder-hero-media__img" style="opacity:0.55;" loading="eager" />
    <div class="voodbuilder-hero-media__shade bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30 lg:bg-gradient-to-r lg:from-zinc-950 lg:via-zinc-950/80 lg:to-transparent"></div>
  </div>
  <div class="voodbuilder-gjs-container relative z-10 flex items-end px-5 pb-16 pt-28 lg:items-center lg:pb-24" data-voodbuilder-role="content" data-voodbuilder-dropzone="content" style="min-height:70vh;">
    <div class="max-w-3xl">
      <div data-voodbuilder-dropzone="copy">
        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-vp-brand-2">Featured</p>
        <h1 class="mb-5 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl">Build pages that go where your stack already is</h1>
        <p class="max-w-xl text-lg leading-relaxed text-zinc-200 lg:text-2xl lg:leading-snug">VoodBuilder is the visual surface for Laravel sites — visual landings, Filament admin, and theme tokens that survive light and dark mode.</p>
      </div>
      <div class="mt-8" data-voodbuilder-dropzone="actions">
        <a href="#" class="inline-flex items-center gap-2 text-sm font-semibold text-white underline decoration-vp-brand-1 decoration-2 underline-offset-4 hover:text-vp-brand-2">Explore VoodBuilder</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function stats(): string
    {
        return <<<'HTML'
<section class="voodbuilder-gjs-section relative z-20 -mt-10 bg-vp-bg-alt px-5 py-10 text-vp-text-2 lg:py-14" data-voodbuilder-section-block="vb-nasa-stats" aria-label="VoodBuilder statistics">
  <div class="voodbuilder-gjs-container">
    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
      <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Sections</p>
        <p class="text-sm text-vp-text-3">Reusable blocks in the library</p>
        <p class="mt-3 text-4xl font-bold tracking-tight text-vp-text-1 lg:text-5xl">60+</p>
      </div>
      <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Templates</p>
        <p class="text-sm text-vp-text-3">Full-page starters ready to apply</p>
        <p class="mt-3 text-4xl font-bold tracking-tight text-vp-text-1 lg:text-5xl">8+</p>
      </div>
      <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Channels</p>
        <p class="text-sm text-vp-text-3">Docs, tutorials, blog themes</p>
        <p class="mt-3 text-4xl font-bold tracking-tight text-vp-text-1 lg:text-5xl">3</p>
      </div>
      <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Stack</p>
        <p class="text-sm text-vp-text-3">Laravel · Filament · Builder</p>
        <p class="mt-3 text-4xl font-bold tracking-tight text-vp-text-1 lg:text-5xl">1</p>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function featured(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg px-5 py-20 text-vp-text-2" data-voodbuilder-section-block="vb-nasa-featured">
  <div class="voodbuilder-gjs-container">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Latest</p>
        <h2 class="text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">Featured stories</h2>
      </div>
      <a href="#" class="text-sm font-semibold text-vp-brand-1 hover:underline">All updates</a>
    </div>
    <div class="grid gap-8 lg:grid-cols-3">
      <a href="#" class="group flex flex-col no-underline lg:col-span-2">
        <div class="overflow-hidden rounded-xl border border-vp-divider">
          <img src="{$image}" alt="" class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105" width="1200" height="675" loading="lazy" />
        </div>
        <p class="mt-4 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Product</p>
        <h3 class="mt-2 text-2xl font-bold text-vp-text-1 group-hover:text-vp-brand-1 lg:text-3xl">Theme tokens that travel from canvas to production</h3>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-vp-text-2">How VoodBuilder sections stay readable when visitors switch appearance — without duplicating light and dark markup.</p>
      </a>
      <div class="flex flex-col gap-6">
        <a href="#" class="group flex gap-4 no-underline border-b border-vp-divider pb-6">
          <img src="{$image}" alt="" class="h-24 w-28 shrink-0 rounded-lg object-cover" width="112" height="96" loading="lazy" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-vp-text-3">Editor</p>
            <h3 class="mt-1 text-lg font-bold text-vp-text-1 group-hover:text-vp-brand-1">Visual editing on Site Pages without a headless CMS</h3>
          </div>
        </a>
        <a href="#" class="group flex gap-4 no-underline border-b border-vp-divider pb-6">
          <img src="{$image}" alt="" class="h-24 w-28 shrink-0 rounded-lg object-cover" width="112" height="96" loading="lazy" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-vp-text-3">Templates</p>
            <h3 class="mt-1 text-lg font-bold text-vp-text-1 group-hover:text-vp-brand-1">Apply a landing, keep or replace existing content</h3>
          </div>
        </a>
        <a href="#" class="group flex gap-4 no-underline">
          <img src="{$image}" alt="" class="h-24 w-28 shrink-0 rounded-lg object-cover" width="112" height="96" loading="lazy" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-vp-text-3">Admin</p>
            <h3 class="mt-1 text-lg font-bold text-vp-text-1 group-hover:text-vp-brand-1">Filament panel for pages, menus and themes</h3>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function teaser(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg px-5 pb-20 text-vp-text-2" data-voodbuilder-section-block="vb-nasa-teaser">
  <div class="voodbuilder-gjs-container">
    <div class="overflow-hidden rounded-2xl border border-vp-divider">
      <img src="{$image}" alt="" class="aspect-[21/9] w-full object-cover" width="1400" height="600" loading="lazy" />
    </div>
    <div class="relative z-10 mx-auto grid gap-10 bg-vp-bg px-0 pt-10 lg:-mt-16 lg:grid-cols-12 lg:gap-8 lg:px-6 lg:pt-16">
      <div class="lg:col-span-7">
        <h2 class="mb-5 text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">One lab for marketing pages and content channels</h2>
        <p class="mb-4 leading-relaxed text-vp-text-2">VoodBuilder keeps landings, documentation and plugin channels in the same Laravel app. Editors work visually; developers stay in the familiar Filament and Vite workflow.</p>
        <p class="mb-6 leading-relaxed text-vp-text-2">Chrome layouts, section blocks and page templates share design tokens so public pages stay coherent when the brand palette changes.</p>
        <a href="#" class="inline-flex text-sm font-semibold text-vp-brand-1 hover:underline">Learn about channels</a>
      </div>
      <aside class="lg:col-span-5">
        <div class="overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-elv shadow-lg">
          <img src="{$image}" alt="" class="aspect-video w-full object-cover" width="640" height="360" loading="lazy" />
          <div class="p-6">
            <h3 class="text-lg font-bold text-vp-text-1">Virtual tour of the editor</h3>
            <p class="mt-2 text-sm text-vp-text-2">Open ?edit=1 on a published page, drag sections from the library, save — HTML and CSS live on the Site Page.</p>
            <a href="#" class="mt-4 inline-flex text-sm font-semibold text-vp-brand-1 hover:underline">Start the tour</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
HTML;
    }

    public static function spotlight(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg-alt px-5 py-20 text-vp-text-2" data-voodbuilder-section-block="vb-nasa-spotlight">
  <div class="voodbuilder-gjs-container grid items-center gap-12 lg:grid-cols-2">
    <div>
      <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Spotlight</p>
      <h2 class="mb-5 text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">Page templates</h2>
      <p class="mb-8 text-lg leading-relaxed text-vp-text-2">Curated full-page starters — Astrolus-style, editorial, and cinematic layouts — composed from section blocks your team can remix.</p>
      <dl class="mb-10 grid grid-cols-3 gap-4">
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-vp-text-3">Blocks</dt>
          <dd class="mt-1 text-2xl font-bold text-vp-text-1">7+</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-vp-text-3">Category</dt>
          <dd class="mt-1 text-2xl font-bold text-vp-text-1">Landing</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-vp-text-3">Tokens</dt>
          <dd class="mt-1 text-2xl font-bold text-vp-text-1">vp-*</dd>
        </div>
      </dl>
      <div class="flex flex-wrap gap-3">
        <a href="#" class="inline-flex rounded-lg bg-vp-brand-1 px-6 py-3 text-sm font-semibold text-white hover:bg-vp-brand-2">Meet templates</a>
        <a href="#" class="inline-flex rounded-lg border border-vp-divider bg-vp-bg px-6 py-3 text-sm font-semibold text-vp-text-1 hover:bg-vp-bg-elv">See all sections</a>
      </div>
    </div>
    <div>
      <img src="{$image}" alt="VoodBuilder page templates" class="w-full rounded-2xl border border-vp-divider object-cover shadow-xl" width="800" height="500" loading="lazy" />
    </div>
  </div>
</section>
HTML;
    }

    public static function missions(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-zinc-950 px-5 py-20 text-white" data-voodbuilder-section-block="vb-nasa-missions">
  <div class="voodbuilder-gjs-container">
    <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
      <div class="max-w-2xl">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-vp-brand-2">Capabilities</p>
        <h2 class="mb-3 text-3xl font-bold tracking-tight text-white md:text-4xl">Active missions for your site</h2>
        <p class="text-lg text-zinc-300">From visual landings to documentation channels — pick the surface that matches the job.</p>
      </div>
      <a href="#" class="text-sm font-semibold text-vp-brand-2 hover:underline">All capabilities</a>
    </div>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <a href="#" class="group overflow-hidden rounded-xl border border-white/10 bg-white/5 no-underline transition hover:border-vp-brand-2/50">
        <img src="{$image}" alt="" class="aspect-[4/3] w-full object-cover opacity-80 transition group-hover:opacity-100" width="400" height="300" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-2">Landings</p>
          <h3 class="mt-2 text-lg font-bold text-white">Visual Site Pages</h3>
        </div>
      </a>
      <a href="#" class="group overflow-hidden rounded-xl border border-white/10 bg-white/5 no-underline transition hover:border-vp-brand-2/50">
        <img src="{$image}" alt="" class="aspect-[4/3] w-full object-cover opacity-80 transition group-hover:opacity-100" width="400" height="300" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-2">Docs</p>
          <h3 class="mt-2 text-lg font-bold text-white">Documentation channel</h3>
        </div>
      </a>
      <a href="#" class="group overflow-hidden rounded-xl border border-white/10 bg-white/5 no-underline transition hover:border-vp-brand-2/50">
        <img src="{$image}" alt="" class="aspect-[4/3] w-full object-cover opacity-80 transition group-hover:opacity-100" width="400" height="300" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-2">Tutorials</p>
          <h3 class="mt-2 text-lg font-bold text-white">Guided learning paths</h3>
        </div>
      </a>
      <a href="#" class="group overflow-hidden rounded-xl border border-white/10 bg-white/5 no-underline transition hover:border-vp-brand-2/50">
        <img src="{$image}" alt="" class="aspect-[4/3] w-full object-cover opacity-80 transition group-hover:opacity-100" width="400" height="300" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-2">Themes</p>
          <h3 class="mt-2 text-lg font-bold text-white">Sub-themes &amp; chrome</h3>
        </div>
      </a>
    </div>
  </div>
</section>
HTML;
    }

    public static function explore(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg px-5 py-20 text-vp-text-2" data-voodbuilder-section-block="vb-nasa-explore">
  <div class="voodbuilder-gjs-container">
    <h2 class="mb-10 text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">Explore more</h2>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <a href="#" class="group overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-elv no-underline transition hover:shadow-lg">
        <img src="{$image}" alt="" class="aspect-[16/10] w-full object-cover" width="400" height="250" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Editor</p>
          <h3 class="mt-2 text-base font-bold text-vp-text-1 group-hover:text-vp-brand-1">How ?edit=1 works for your team</h3>
        </div>
      </a>
      <a href="#" class="group overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-elv no-underline transition hover:shadow-lg">
        <img src="{$image}" alt="" class="aspect-[16/10] w-full object-cover" width="400" height="250" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Design</p>
          <h3 class="mt-2 text-base font-bold text-vp-text-1 group-hover:text-vp-brand-1">Building sections with theme tokens</h3>
        </div>
      </a>
      <a href="#" class="group overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-elv no-underline transition hover:shadow-lg">
        <img src="{$image}" alt="" class="aspect-[16/10] w-full object-cover" width="400" height="250" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Library</p>
          <h3 class="mt-2 text-base font-bold text-vp-text-1 group-hover:text-vp-brand-1">Page templates vs section blocks</h3>
        </div>
      </a>
      <a href="#" class="group overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-elv no-underline transition hover:shadow-lg">
        <img src="{$image}" alt="" class="aspect-[16/10] w-full object-cover" width="400" height="250" loading="lazy" />
        <div class="p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-vp-brand-1">Ops</p>
          <h3 class="mt-2 text-base font-bold text-vp-text-1 group-hover:text-vp-brand-1">Permissions and publish flow</h3>
        </div>
      </a>
    </div>
  </div>
</section>
HTML;
    }
}
