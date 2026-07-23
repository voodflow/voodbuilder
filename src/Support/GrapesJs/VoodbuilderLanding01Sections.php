<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Astrolus-inspired landing sections for VoodBuilder (theme tokens = light/dark).
 */
final class VoodbuilderLanding01Sections
{
    public const TEMPLATE_NAME = 'VoodBuilder landing 01';

    public static function registerBlocks(): void
    {
        $category = 'Voodbuilder / Landing 01';

        foreach (self::blockDefinitions() as $definition) {
            Voodbuilder::grapesJsBlock(
                $definition['id'],
                $definition['label'],
                $category,
                $definition['content'],
                ['title' => $definition['label']],
            );
        }
    }

    /**
     * @return list<array{id: string, label: string, content: string}>
     */
    public static function blockDefinitions(): array
    {
        return [
            [
                'id' => 'vb-landing01-hero',
                'label' => 'Landing 01 · Hero',
                'content' => self::hero(),
            ],
            [
                'id' => 'vb-landing01-features',
                'label' => 'Landing 01 · Features',
                'content' => self::features(),
            ],
            [
                'id' => 'vb-landing01-solution',
                'label' => 'Landing 01 · Solution',
                'content' => self::solution(),
            ],
            [
                'id' => 'vb-landing01-testimonials',
                'label' => 'Landing 01 · Testimonials',
                'content' => self::testimonials(),
            ],
            [
                'id' => 'vb-landing01-articles',
                'label' => 'Landing 01 · Articles',
                'content' => self::articles(),
            ],
            [
                'id' => 'vb-landing01-cta',
                'label' => 'Landing 01 · CTA',
                'content' => self::cta(),
            ],
        ];
    }

    public static function pageHtml(): string
    {
        return self::hero()
            .self::features()
            .self::solution()
            .self::testimonials()
            .self::articles()
            .self::cta();
    }

    public static function hero(): string
    {
        return <<<'HTML'
<section class="voodbuilder-gjs-section relative bg-vp-bg text-vp-text-2" data-voodbuilder-section-block="vb-landing01-hero">
  <div aria-hidden="true" class="pointer-events-none absolute inset-0 grid grid-cols-2 -space-x-52 opacity-40">
    <div class="h-56 bg-gradient-to-br from-vp-brand-1 to-vp-brand-2 blur-[106px]"></div>
    <div class="h-32 bg-gradient-to-r from-vp-brand-2 to-vp-brand-3 blur-[106px]"></div>
  </div>
  <div class="voodbuilder-gjs-container relative px-5 pb-16 pt-28">
    <div class="mx-auto max-w-3xl text-center">
      <h1 class="text-balance text-5xl font-bold text-vp-text-1 md:text-6xl xl:text-7xl">Ship marketing pages with <span class="text-vp-brand-1">VoodBuilder.</span></h1>
      <p class="mt-8 text-lg leading-relaxed text-vp-text-2">Visual landings for Laravel teams — GrapesJS editing, Filament admin, and theme tokens that follow light and dark mode automatically.</p>
      <div class="mt-12 flex flex-wrap justify-center gap-4">
        <a href="#" class="inline-flex h-11 items-center justify-center rounded-full bg-vp-brand-1 px-8 text-base font-semibold text-white transition hover:bg-vp-brand-2">Get started</a>
        <a href="#" class="inline-flex h-11 items-center justify-center rounded-full border border-vp-divider bg-vp-bg-alt px-8 text-base font-semibold text-vp-text-1 transition hover:bg-vp-bg-elv">Learn more</a>
      </div>
      <div class="mt-16 hidden justify-between gap-6 border-y border-vp-divider py-8 sm:flex">
        <div class="text-left">
          <h6 class="text-lg font-semibold text-vp-text-1">Visual editing</h6>
          <p class="mt-2 text-sm text-vp-text-3">Drag sections, publish live</p>
        </div>
        <div class="text-left">
          <h6 class="text-lg font-semibold text-vp-text-1">Theme aware</h6>
          <p class="mt-2 text-sm text-vp-text-3">Light and dark out of the box</p>
        </div>
        <div class="text-left">
          <h6 class="text-lg font-semibold text-vp-text-1">Laravel native</h6>
          <p class="mt-2 text-sm text-vp-text-3">Filament, Livewire, Vite</p>
        </div>
      </div>
    </div>
    <div class="mt-12 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
      <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-3 py-4 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Laravel</div>
      <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-3 py-4 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Filament</div>
      <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-3 py-4 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Livewire</div>
      <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-3 py-4 text-xs font-semibold uppercase tracking-wide text-vp-text-3">GrapesJS</div>
      <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-3 py-4 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Tailwind</div>
      <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-3 py-4 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Vite</div>
    </div>
  </div>
</section>
HTML;
    }

    public static function features(): string
    {
        return <<<'HTML'
<section class="voodbuilder-gjs-section bg-vp-bg py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing01-features">
  <div class="voodbuilder-gjs-container px-5">
    <div class="md:w-2/3 lg:w-1/2">
      <p class="text-sm font-semibold uppercase tracking-widest text-vp-brand-1">Features</p>
      <h2 class="my-6 text-2xl font-bold text-vp-text-1 md:text-4xl">A builder-first toolkit for Laravel marketing sites</h2>
      <p class="text-vp-text-2">Compose pages from reusable sections, keep brand colours in sync with your admin palette, and ship without a separate headless CMS.</p>
    </div>
    <div class="mt-16 grid overflow-hidden rounded-3xl border border-vp-divider sm:grid-cols-2 lg:grid-cols-4">
      <div class="group relative border-vp-divider bg-vp-bg-elv p-8 transition hover:z-[1] hover:shadow-xl sm:border-r lg:border-b-0">
        <h5 class="text-xl font-semibold text-vp-text-1 transition group-hover:text-vp-brand-1">GrapesJS editor</h5>
        <p class="mt-3 text-sm leading-relaxed text-vp-text-2">Edit published pages in the browser with drag-and-drop sections and live preview.</p>
        <a href="#" class="mt-6 inline-flex items-center text-sm font-medium text-vp-brand-1">Read more</a>
      </div>
      <div class="group relative border-vp-divider bg-vp-bg-elv p-8 transition hover:z-[1] hover:shadow-xl sm:border-r-0 lg:border-r lg:border-b-0">
        <h5 class="text-xl font-semibold text-vp-text-1 transition group-hover:text-vp-brand-1">Theme tokens</h5>
        <p class="mt-3 text-sm leading-relaxed text-vp-text-2">Sections use bg-vp-* and text-vp-* so light and dark mode stay consistent.</p>
        <a href="#" class="mt-6 inline-flex items-center text-sm font-medium text-vp-brand-1">Read more</a>
      </div>
      <div class="group relative border-t border-vp-divider bg-vp-bg-elv p-8 transition hover:z-[1] hover:shadow-xl sm:border-r lg:border-t-0">
        <h5 class="text-xl font-semibold text-vp-text-1 transition group-hover:text-vp-brand-1">Page templates</h5>
        <p class="mt-3 text-sm leading-relaxed text-vp-text-2">Start from curated layouts in the template library and customise every block.</p>
        <a href="#" class="mt-6 inline-flex items-center text-sm font-medium text-vp-brand-1">Read more</a>
      </div>
      <div class="group relative border-t border-vp-divider bg-vp-bg-elv p-8 transition hover:z-[1] hover:shadow-xl lg:border-t-0">
        <h5 class="text-xl font-semibold text-vp-text-1 transition group-hover:text-vp-brand-1">Filament admin</h5>
        <p class="mt-3 text-sm leading-relaxed text-vp-text-2">Manage pages, menus, themes and settings from the same Laravel panel.</p>
        <a href="#" class="mt-6 inline-flex items-center text-sm font-medium text-vp-brand-1">Read more</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function solution(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg-alt py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing01-solution">
  <div class="voodbuilder-gjs-container px-5">
    <p class="mb-6 text-sm font-semibold uppercase tracking-widest text-vp-brand-1">Solution</p>
    <div class="flex flex-col-reverse items-center gap-12 md:flex-row md:gap-16">
      <div class="w-full md:w-1/2">
        <h2 class="text-3xl font-bold text-vp-text-1 md:text-4xl">Built for teams who ship Laravel sites</h2>
        <p class="my-8 leading-relaxed text-vp-text-2">VoodBuilder keeps marketing pages, documentation channels and plugin content in one stack. Editors work visually; developers stay in the familiar Laravel workflow.</p>
        <div class="space-y-6 divide-y divide-vp-divider">
          <div class="flex gap-4 md:items-center">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-vp-brand-1/15 text-vp-brand-1">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0112 2.25c2.43 0 4.817.178 7.152.52 1.978.292 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.678-3.348 3.97a48.901 48.901 0 01-3.476.383.39.39 0 00-.297.17l-2.755 4.133a.75.75 0 01-1.248 0l-2.755-4.133a.39.39 0 00-.297-.17 48.9 48.9 0 01-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.68 3.348-3.97z" clip-rule="evenodd" /></svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-vp-text-1">Collaborate anytime</h3>
              <p class="text-sm text-vp-text-3">Share draft landings with stakeholders without leaving the site.</p>
            </div>
          </div>
          <div class="flex gap-4 pt-6 md:items-center">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-vp-brand-2/15 text-vp-brand-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-vp-text-1">One stack, many channels</h3>
              <p class="text-sm text-vp-text-3">Landings, docs and blog themes share the same design tokens.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="w-full md:w-1/2">
        <img src="{$image}" alt="VoodBuilder workspace" class="w-full rounded-2xl border border-vp-divider object-cover shadow-lg" width="800" height="500" loading="lazy" />
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function testimonials(): string
    {
        $avatar = self::avatarDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing01-testimonials">
  <div class="voodbuilder-gjs-container px-5">
    <h2 class="mb-16 text-center text-2xl font-bold text-vp-text-1 md:text-4xl">Teams already building with VoodBuilder</h2>
    <div class="gap-8 space-y-8 md:columns-2 lg:columns-3">
      <div class="break-inside-avoid rounded-3xl border border-vp-divider bg-vp-bg-elv p-8 shadow-sm">
        <div class="flex gap-4">
          <img class="h-12 w-12 rounded-full object-cover" src="{$avatar}" alt="" width="48" height="48" loading="lazy" />
          <div>
            <h6 class="text-lg font-medium text-vp-text-1">Giulia Rossi</h6>
            <p class="text-sm text-vp-text-3">Product designer</p>
          </div>
        </div>
        <p class="mt-8 leading-relaxed">We replaced scattered landing repos with VoodBuilder. Marketing iterates in GrapesJS while engineering keeps Laravel and Filament as the source of truth.</p>
      </div>
      <div class="break-inside-avoid rounded-3xl border border-vp-divider bg-vp-bg-elv p-8 shadow-sm">
        <div class="flex gap-4">
          <img class="h-12 w-12 rounded-full object-cover" src="{$avatar}" alt="" width="48" height="48" loading="lazy" />
          <div>
            <h6 class="text-lg font-medium text-vp-text-1">Marco Bianchi</h6>
            <p class="text-sm text-vp-text-3">Growth lead</p>
          </div>
        </div>
        <p class="mt-8 leading-relaxed">Template library plus theme tokens meant our campaign pages finally match the rest of the product site — including dark mode.</p>
      </div>
      <div class="break-inside-avoid rounded-3xl border border-vp-divider bg-vp-bg-elv p-8 shadow-sm">
        <div class="flex gap-4">
          <img class="h-12 w-12 rounded-full object-cover" src="{$avatar}" alt="" width="48" height="48" loading="lazy" />
          <div>
            <h6 class="text-lg font-medium text-vp-text-1">Elena Conti</h6>
            <p class="text-sm text-vp-text-3">Full-stack developer</p>
          </div>
        </div>
        <p class="mt-8 leading-relaxed">No headless CMS detour. Pages live as Site Pages, CSS follows our palette, and ?edit=1 is enough for editors.</p>
      </div>
      <div class="break-inside-avoid rounded-3xl border border-vp-divider bg-vp-bg-elv p-8 shadow-sm">
        <div class="flex gap-4">
          <img class="h-12 w-12 rounded-full object-cover" src="{$avatar}" alt="" width="48" height="48" loading="lazy" />
          <div>
            <h6 class="text-lg font-medium text-vp-text-1">Luca Ferri</h6>
            <p class="text-sm text-vp-text-3">Agency founder</p>
          </div>
        </div>
        <p class="mt-8 leading-relaxed">We hand clients a VoodBuilder install and they keep shipping landings without calling us for every headline change.</p>
      </div>
      <div class="break-inside-avoid rounded-3xl border border-vp-divider bg-vp-bg-elv p-8 shadow-sm">
        <div class="flex gap-4">
          <img class="h-12 w-12 rounded-full object-cover" src="{$avatar}" alt="" width="48" height="48" loading="lazy" />
          <div>
            <h6 class="text-lg font-medium text-vp-text-1">Sara Greco</h6>
            <p class="text-sm text-vp-text-3">Content ops</p>
          </div>
        </div>
        <p class="mt-8 leading-relaxed">Menus, footers and page templates stay organised. Editors stay focused on copy instead of fighting layouts.</p>
      </div>
      <div class="break-inside-avoid rounded-3xl border border-vp-divider bg-vp-bg-elv p-8 shadow-sm">
        <div class="flex gap-4">
          <img class="h-12 w-12 rounded-full object-cover" src="{$avatar}" alt="" width="48" height="48" loading="lazy" />
          <div>
            <h6 class="text-lg font-medium text-vp-text-1">Andrea Neri</h6>
            <p class="text-sm text-vp-text-3">CTO</p>
          </div>
        </div>
        <p class="mt-8 leading-relaxed">VoodBuilder fits our monorepo: packages for docs and tutorials, one visual layer for marketing. That clarity matters.</p>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function articles(): string
    {
        $image = GrapesJsPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section bg-vp-bg-alt py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing01-articles">
  <div class="voodbuilder-gjs-container px-5">
    <div class="mb-12 space-y-3 text-center">
      <h2 class="text-3xl font-bold text-vp-text-1 md:text-4xl">From the VoodBuilder blog</h2>
      <p class="mx-auto max-w-2xl text-vp-text-2">Guides on visual editing, theme tokens and shipping landings without leaving Laravel.</p>
    </div>
    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
      <article class="rounded-3xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm sm:p-8">
        <div class="overflow-hidden rounded-xl">
          <img src="{$image}" alt="" class="h-56 w-full object-cover transition duration-500 hover:scale-105" width="800" height="500" loading="lazy" />
        </div>
        <h3 class="mt-6 text-2xl font-semibold text-vp-text-1">Design landings that survive dark mode</h3>
        <p class="mt-4 mb-6 text-vp-text-2">How VoodBuilder theme tokens keep sections readable when users switch appearance.</p>
        <a href="#" class="inline-block text-sm font-medium text-vp-brand-1">Read more</a>
      </article>
      <article class="rounded-3xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm sm:p-8">
        <div class="overflow-hidden rounded-xl">
          <img src="{$image}" alt="" class="h-56 w-full object-cover transition duration-500 hover:scale-105" width="800" height="500" loading="lazy" />
        </div>
        <h3 class="mt-6 text-2xl font-semibold text-vp-text-1">Page templates vs section blocks</h3>
        <p class="mt-4 mb-6 text-vp-text-2">When to start from a full-page template and when to compose sections one by one.</p>
        <a href="#" class="inline-block text-sm font-medium text-vp-brand-1">Read more</a>
      </article>
      <article class="rounded-3xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm sm:p-8">
        <div class="overflow-hidden rounded-xl">
          <img src="{$image}" alt="" class="h-56 w-full object-cover transition duration-500 hover:scale-105" width="800" height="500" loading="lazy" />
        </div>
        <h3 class="mt-6 text-2xl font-semibold text-vp-text-1">GrapesJS in production Laravel apps</h3>
        <p class="mt-4 mb-6 text-vp-text-2">Permissions, publish flow and keeping the public chrome in sync with the canvas.</p>
        <a href="#" class="inline-block text-sm font-medium text-vp-brand-1">Read more</a>
      </article>
    </div>
  </div>
</section>
HTML;
    }

    public static function cta(): string
    {
        $avatar = self::avatarDataUri();

        return <<<HTML
<section class="voodbuilder-gjs-section relative bg-vp-bg py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing01-cta">
  <div aria-hidden="true" class="pointer-events-none absolute inset-0 m-auto grid h-max w-full grid-cols-2 -space-x-52 opacity-40">
    <div class="h-56 bg-gradient-to-br from-vp-brand-1 to-vp-brand-2 blur-[106px]"></div>
    <div class="h-32 bg-gradient-to-r from-vp-brand-2 to-vp-brand-3 blur-[106px]"></div>
  </div>
  <div class="voodbuilder-gjs-container relative px-5">
    <div class="flex items-center justify-center -space-x-2">
      <img loading="lazy" width="32" height="32" src="{$avatar}" alt="" class="h-8 w-8 rounded-full object-cover" />
      <img loading="lazy" width="48" height="48" src="{$avatar}" alt="" class="h-12 w-12 rounded-full object-cover" />
      <img loading="lazy" width="64" height="64" src="{$avatar}" alt="" class="z-10 h-16 w-16 rounded-full object-cover ring-2 ring-vp-bg" />
      <img loading="lazy" width="48" height="48" src="{$avatar}" alt="" class="h-12 w-12 rounded-full object-cover" />
      <img loading="lazy" width="32" height="32" src="{$avatar}" alt="" class="h-8 w-8 rounded-full object-cover" />
    </div>
    <div class="mx-auto mt-8 max-w-2xl space-y-6 text-center">
      <h2 class="text-4xl font-bold text-vp-text-1 md:text-5xl">Start building with VoodBuilder</h2>
      <p class="text-xl text-vp-text-2">Join teams shipping Laravel marketing pages with a visual editor, theme-aware sections and Filament admin.</p>
      <div class="flex flex-wrap justify-center gap-4">
        <a href="#" class="inline-flex h-12 items-center justify-center rounded-full bg-vp-brand-1 px-8 text-base font-semibold text-white transition hover:bg-vp-brand-2">Get started</a>
        <a href="#" class="inline-flex h-12 items-center justify-center rounded-full border border-vp-divider bg-vp-bg-alt px-8 text-base font-semibold text-vp-text-1 transition hover:bg-vp-bg-elv">Explore templates</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    private static function avatarDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">'
            .'<rect width="96" height="96" rx="48" fill="#cbd5e1"/>'
            .'<circle cx="48" cy="38" r="16" fill="#94a3b8"/>'
            .'<path d="M16 82c4-18 20-28 32-28s28 10 32 28" fill="#94a3b8"/>'
            .'</svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
}
