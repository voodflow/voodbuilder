<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Community landing sections retained in Core (articles + CTA).
 * Companion sections (hero, features, solution, testimonials) live in Elements.
 */
final class VoodbuilderLanding01Sections
{
    public const TEMPLATE_NAME = 'Landing 01';

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
                'id' => 'vb-landing01-articles',
                'label' => 'Article cards · 3',
                'category' => 'Articles',
                'content' => self::articles(),
            ],
            [
                'id' => 'vb-landing01-cta',
                'label' => 'Community CTA',
                'category' => 'CTA',
                'content' => self::cta(),
            ],
        ];
    }

    public static function pageHtml(): string
    {
        return self::articles()
            .self::cta();
    }

    public static function articles(): string
    {
        $image = EditorPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-editor-section bg-vp-bg-alt py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing01-articles">
  <div class="voodbuilder-editor-container px-5">
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
        <h3 class="mt-6 text-2xl font-semibold text-vp-text-1">Visual builder in production Laravel apps</h3>
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
<section class="voodbuilder-editor-section relative bg-vp-bg py-20 text-vp-text-2" data-voodbuilder-section-block="vb-landing01-cta">
  <div aria-hidden="true" class="pointer-events-none absolute inset-0 m-auto grid h-max w-full grid-cols-2 -space-x-52 opacity-40">
    <div class="h-56 bg-gradient-to-br from-vp-brand-1 to-vp-brand-2 blur-[106px]"></div>
    <div class="h-32 bg-gradient-to-r from-vp-brand-2 to-vp-brand-3 blur-[106px]"></div>
  </div>
  <div class="voodbuilder-editor-container relative px-5">
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
