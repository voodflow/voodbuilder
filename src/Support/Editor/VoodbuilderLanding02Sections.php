<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Daiva-inspired editorial landing for VoodBuilder (theme tokens = light/dark).
 *
 * @see https://github.com/lbegey/tw-daiva-tpl
 */
final class VoodbuilderLanding02Sections
{
    public const TEMPLATE_NAME = 'Landing 02';

    public static function registerBlocks(): void
    {
        foreach (self::blockDefinitions() as $definition) {
            Voodbuilder::editorBlock(
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
                'id' => 'vb-landing02-hero',
                'label' => 'Editorial hero',
                'category' => 'Hero',
                'content' => self::hero(),
            ],
            [
                'id' => 'vb-landing02-trust',
                'label' => 'Trust logos',
                'category' => 'Content',
                'content' => self::trust(),
            ],
            [
                'id' => 'vb-landing02-features',
                'label' => 'Feature grid · 2×2',
                'category' => 'Features',
                'content' => self::features(),
            ],
            [
                'id' => 'vb-landing02-impact',
                'label' => 'Impact split',
                'category' => 'Content',
                'content' => self::impact(),
            ],
            [
                'id' => 'vb-landing02-toolkit',
                'label' => 'Toolkit pills',
                'category' => 'Features',
                'content' => self::toolkit(),
            ],
            [
                'id' => 'vb-landing02-articles',
                'label' => 'Article duo',
                'category' => 'Articles',
                'content' => self::articles(),
            ],
            [
                'id' => 'vb-landing02-faq',
                'label' => 'FAQ accordion',
                'category' => 'Content',
                'content' => self::faq(),
            ],
        ];
    }

    public static function pageHtml(): string
    {
        return self::hero()
            .self::trust()
            .self::features()
            .self::impact()
            .self::toolkit()
            .self::articles()
            .self::faq();
    }

    public static function hero(): string
    {
        $image = EditorPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-editor-section bg-vp-bg-alt px-5 py-24 text-vp-text-2" data-voodbuilder-section-block="vb-landing02-hero">
  <div class="voodbuilder-editor-container">
    <div class="flex flex-col gap-10 sm:flex-row sm:items-center sm:gap-12">
      <div class="hidden w-full max-w-[400px] shrink-0 sm:block">
        <img class="w-full rounded-2xl border border-vp-divider object-cover shadow-sm" src="{$image}" alt="VoodBuilder editor" width="800" height="500" loading="lazy" />
      </div>
      <div class="flex w-full flex-col items-start gap-4">
        <small class="mb-2 border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Built with care</small>
        <h1 class="font-serif text-3xl font-bold tracking-tight text-vp-text-1 md:text-5xl xl:text-6xl">Built by Laravel teams for Laravel teams</h1>
        <p class="max-w-xl text-base leading-relaxed text-vp-text-2">VoodBuilder turns Site Pages into visual landings — the visual editor in the browser, Filament in the admin, theme tokens that follow light and dark mode.</p>
        <a href="#" class="mt-4 inline-flex rounded-full bg-vp-brand-1 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-vp-brand-2">Let's start</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function trust(): string
    {
        return <<<'HTML'
<section class="voodbuilder-editor-section bg-vp-bg px-5 py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing02-trust">
  <div class="voodbuilder-editor-container">
    <div class="flex flex-col gap-8">
      <small class="border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Trusted by builders</small>
      <div class="grid grid-cols-2 items-center gap-4 sm:grid-cols-4">
        <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-4 py-5 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Laravel</div>
        <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-4 py-5 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Filament</div>
        <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-4 py-5 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Livewire</div>
        <div class="flex items-center justify-center rounded-xl border border-vp-divider bg-vp-bg-elv px-4 py-5 text-xs font-semibold uppercase tracking-wide text-vp-text-3">Builder</div>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function features(): string
    {
        return <<<'HTML'
<section class="voodbuilder-editor-section bg-vp-bg px-5 py-24 text-vp-text-2" data-voodbuilder-section-block="vb-landing02-features">
  <div class="voodbuilder-editor-container">
    <h2 class="mb-12 font-serif text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">Editor first</h2>
    <div class="grid gap-10 md:grid-cols-2 md:gap-8">
      <div class="flex flex-col gap-2">
        <small class="border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Visual pages</small>
        <h3 class="font-serif text-xl font-bold text-vp-text-1">Visual editing on Site Pages</h3>
        <p class="text-sm leading-relaxed text-vp-text-2">Drag sections, tweak copy and publish without leaving your Laravel app.</p>
        <a href="#" class="mt-1 text-sm font-medium text-vp-brand-1 underline">See more</a>
      </div>
      <div class="flex flex-col gap-2">
        <small class="border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Design system</small>
        <h3 class="font-serif text-xl font-bold text-vp-text-1">Theme-aware sections</h3>
        <p class="text-sm leading-relaxed text-vp-text-2">bg-vp-* and text-vp-* keep landings in sync with light and dark mode.</p>
        <a href="#" class="mt-1 text-sm font-medium text-vp-brand-1 underline">See more</a>
      </div>
      <div class="flex flex-col gap-2">
        <small class="border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Library</small>
        <h3 class="font-serif text-xl font-bold text-vp-text-1">Templates &amp; blocks</h3>
        <p class="text-sm leading-relaxed text-vp-text-2">Start from curated layouts or compose from the section library.</p>
        <a href="#" class="mt-1 text-sm font-medium text-vp-brand-1 underline">See more</a>
      </div>
      <div class="flex flex-col gap-2">
        <small class="border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Admin</small>
        <h3 class="font-serif text-xl font-bold text-vp-text-1">Filament control plane</h3>
        <p class="text-sm leading-relaxed text-vp-text-2">Pages, menus, themes and settings live in the same panel your team already uses.</p>
        <a href="#" class="mt-1 text-sm font-medium text-vp-brand-1 underline">See more</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function impact(): string
    {
        $image = EditorPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-editor-section bg-vp-bg-alt px-5 py-24 text-vp-text-2" data-voodbuilder-section-block="vb-landing02-impact">
  <div class="voodbuilder-editor-container">
    <div class="flex flex-col-reverse items-start gap-10 sm:flex-row-reverse sm:items-center sm:gap-12">
      <div class="w-full max-w-[400px] shrink-0">
        <img class="w-full rounded-2xl border border-vp-divider object-cover shadow-sm" src="{$image}" alt="VoodBuilder workspace" width="800" height="500" loading="lazy" />
      </div>
      <div class="flex w-full flex-col items-end gap-4 text-right">
        <small class="mb-2 border-r-2 border-vp-brand-1 pr-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Proven impact</small>
        <h2 class="font-serif text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">Ship landings without the CMS detour</h2>
        <p class="max-w-xl text-base leading-relaxed text-vp-text-2">Marketing iterates on the canvas. Engineering keeps Laravel, Vite and Filament as the source of truth — one stack, clearer ownership.</p>
        <a href="#" class="mt-4 inline-flex rounded-full bg-vp-brand-1 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-vp-brand-2">Let's start</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function toolkit(): string
    {
        return <<<'HTML'
<section class="voodbuilder-editor-section bg-vp-bg px-5 py-24 text-vp-text-2" data-voodbuilder-section-block="vb-landing02-toolkit">
  <div class="voodbuilder-editor-container">
    <div class="flex flex-col gap-4">
      <small class="mb-2 border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Toolkit</small>
      <h2 class="font-serif text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">All in one</h2>
      <p class="max-w-2xl text-base leading-relaxed text-vp-text-2">Sections, templates, chrome layouts and content channels share the same design tokens — so your public site stays coherent.</p>
      <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="flex items-center gap-4 rounded-full border border-vp-divider bg-vp-bg-alt px-6 py-4 sm:px-10">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-vp-bg-elv text-vp-brand-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z" /></svg>
          </div>
          <div>
            <h4 class="font-serif font-bold text-vp-text-1">Page templates</h4>
            <small class="text-vp-text-3">Curated full-page starters</small>
          </div>
        </div>
        <div class="flex items-center gap-4 rounded-full border border-vp-divider bg-vp-bg-alt px-6 py-4 sm:px-10">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-vp-bg-elv text-vp-brand-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6zM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25z" clip-rule="evenodd" /></svg>
          </div>
          <div>
            <h4 class="font-serif font-bold text-vp-text-1">Section blocks</h4>
            <small class="text-vp-text-3">Heroes, CTAs, grids and more</small>
          </div>
        </div>
        <div class="flex items-center gap-4 rounded-full border border-vp-divider bg-vp-bg-alt px-6 py-4 sm:px-10">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-vp-bg-elv text-vp-brand-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm0 1.5a8.25 8.25 0 100 16.5 8.25 8.25 0 000-16.5zM10.5 8.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM8.25 15a.75.75 0 01.75-.75h6a.75.75 0 010 1.5h-6A.75.75 0 018.25 15z" clip-rule="evenodd" /></svg>
          </div>
          <div>
            <h4 class="font-serif font-bold text-vp-text-1">Theme tokens</h4>
            <small class="text-vp-text-3">Light and dark by default</small>
          </div>
        </div>
        <div class="flex items-center gap-4 rounded-full border border-vp-divider bg-vp-bg-alt px-6 py-4 sm:px-10">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-vp-bg-elv text-vp-brand-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.715 6.715 0 0112 13.5c1.751 0 3.358.672 4.547 1.776.4.37.65.89.65 1.44v.534A2.25 2.25 0 0114.947 19.5H9.053a2.25 2.25 0 01-2.25-2.25v-.534c0-.55.25-1.07.65-1.44.4-.37.88-.66 1.407-.909z" clip-rule="evenodd" /></svg>
          </div>
          <div>
            <h4 class="font-serif font-bold text-vp-text-1">Team editing</h4>
            <small class="text-vp-text-3">Permission-aware ?edit=1</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
HTML;
    }

    public static function articles(): string
    {
        $image = EditorPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-editor-section bg-vp-bg px-5 py-24 text-vp-text-2" data-voodbuilder-section-block="vb-landing02-articles">
  <div class="voodbuilder-editor-container">
    <div class="flex flex-col items-start gap-4">
      <small class="mb-2 border-l-2 border-vp-brand-1 pl-3 text-xs font-semibold uppercase tracking-widest text-vp-text-3">Insights</small>
      <h2 class="font-serif text-3xl font-bold tracking-tight text-vp-text-1 md:text-4xl">From the VoodBuilder blog</h2>
      <p class="max-w-2xl text-base leading-relaxed text-vp-text-2">Practical notes on visual editing, theme tokens and shipping marketing pages inside Laravel.</p>
      <div class="mt-8 flex w-full flex-col gap-10 sm:flex-row sm:gap-8">
        <a href="#" class="flex w-full flex-col gap-4 no-underline">
          <h3 class="font-serif text-2xl font-bold text-vp-text-1">Own your marketing stack</h3>
          <img class="w-full rounded-xl border border-vp-divider object-cover" src="{$image}" alt="" width="800" height="500" loading="lazy" />
          <p class="text-sm text-vp-text-2">Keep landings, menus and SEO defaults in one Laravel codebase — no extra headless CMS.</p>
          <span class="text-xs font-medium text-vp-brand-1 underline">See more</span>
        </a>
        <a href="#" class="flex w-full flex-col gap-4 no-underline">
          <h3 class="font-serif text-2xl font-bold text-vp-text-1">Theme tokens that travel</h3>
          <img class="w-full rounded-xl border border-vp-divider object-cover" src="{$image}" alt="" width="800" height="500" loading="lazy" />
          <p class="text-sm text-vp-text-2">How VoodBuilder sections stay readable when visitors switch between light and dark.</p>
          <span class="text-xs font-medium text-vp-brand-1 underline">See more</span>
        </a>
      </div>
      <a href="#" class="mt-6 inline-flex rounded-full bg-vp-brand-1 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-vp-brand-2">More articles</a>
    </div>
  </div>
</section>
HTML;
    }

    public static function faq(): string
    {
        return <<<'HTML'
<section class="voodbuilder-editor-section bg-vp-bg-alt px-5 py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing02-faq">
  <div class="voodbuilder-editor-container">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 lg:gap-12">
      <div>
        <h2 class="font-serif text-2xl font-bold text-vp-text-1">Questions about VoodBuilder</h2>
        <p class="mt-2 text-sm text-vp-text-3">Short answers for teams evaluating the visual page layer.</p>
      </div>
      <div class="space-y-2 lg:col-span-2">
        <details class="group border-b border-vp-divider py-3">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-medium text-vp-text-1">
            <span>How does the editor work?</span>
            <svg class="h-5 w-5 shrink-0 text-vp-text-3 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6v12m6-6H6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </summary>
          <p class="pt-2 text-sm leading-relaxed text-vp-text-2">Open a published Site Page with ?edit=1. The visual editor loads your layout; save persists HTML and CSS on the page record.</p>
        </details>
        <details class="group border-b border-vp-divider py-3">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-medium text-vp-text-1">
            <span>Do sections support dark mode?</span>
            <svg class="h-5 w-5 shrink-0 text-vp-text-3 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6v12m6-6H6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </summary>
          <p class="pt-2 text-sm leading-relaxed text-vp-text-2">Yes. Prefer bg-vp-* and text-vp-* tokens so colours follow the active theme palette automatically.</p>
        </details>
        <details class="group border-b border-vp-divider py-3">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-medium text-vp-text-1">
            <span>Can we cancel or change a template later?</span>
            <svg class="h-5 w-5 shrink-0 text-vp-text-3 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6v12m6-6H6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </summary>
          <p class="pt-2 text-sm leading-relaxed text-vp-text-2">Templates only seed the canvas. You can replace, keep existing content or edit every block afterwards.</p>
        </details>
        <details class="group border-b border-vp-divider py-3">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-medium text-vp-text-1">
            <span>Is it safe for production?</span>
            <svg class="h-5 w-5 shrink-0 text-vp-text-3 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6v12m6-6H6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </summary>
          <p class="pt-2 text-sm leading-relaxed text-vp-text-2">Editing is gated by Laravel permissions. Public visitors see the published page; the builder never loads for guests.</p>
        </details>
        <details class="group border-b border-vp-divider py-3">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-medium text-vp-text-1">
            <span>Can multiple editors work on pages?</span>
            <svg class="h-5 w-5 shrink-0 text-vp-text-3 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6v12m6-6H6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </summary>
          <p class="pt-2 text-sm leading-relaxed text-vp-text-2">Anyone with the builder permission can open ?edit=1. Coordinate saves like any shared content workflow.</p>
        </details>
        <details class="group border-b border-vp-divider py-3">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-medium text-vp-text-1">
            <span>Is VoodBuilder a free service?</span>
            <svg class="h-5 w-5 shrink-0 text-vp-text-3 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6v12m6-6H6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          </summary>
          <p class="pt-2 text-sm leading-relaxed text-vp-text-2">VoodBuilder is a package you run in your own Laravel app — you own the hosting, data and deployment.</p>
        </details>
      </div>
    </div>
  </div>
</section>
HTML;
    }
}
