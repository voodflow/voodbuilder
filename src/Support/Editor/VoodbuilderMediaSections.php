<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Community media section retained in Core (background image hero).
 * Companion media (bg-video, sliders) live in Elements.
 */
final class VoodbuilderMediaSections
{
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
                'id' => 'vb-bg-image',
                'label' => 'Background image',
                'category' => 'Hero',
                'content' => self::backgroundImage(),
            ],
        ];
    }

    public static function backgroundImage(): string
    {
        $image = EditorPlaceholderNormalizer::neutralImageDataUri();

        return <<<HTML
<section class="voodbuilder-editor-section relative overflow-hidden bg-zinc-950 text-white" data-voodbuilder-section-block="vb-bg-image" data-vb-bg-size="cover" data-vb-bg-position="center" data-vb-bg-opacity="0.55" data-vb-min-height="70vh" style="min-height:70vh;">
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media" aria-hidden="true" style="position:absolute;inset:0;overflow:hidden;">
    <img src="{$image}" alt="" class="voodbuilder-hero-media__img" style="position:absolute;inset:0;display:block;width:100%;height:100%;max-width:none;opacity:0.55;object-fit:cover;object-position:center;" loading="eager" />
    <div class="voodbuilder-hero-media__shade bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30 lg:bg-gradient-to-r lg:from-zinc-950 lg:via-zinc-950/80 lg:to-transparent" data-voodbuilder-role="shade" style="pointer-events:none;position:absolute;inset:0;"></div>
  </div>
  <div class="voodbuilder-editor-container relative z-10 flex items-end px-5 pb-16 pt-28 lg:items-center lg:pb-24" data-voodbuilder-role="content" style="min-height:70vh;">
    <div class="max-w-3xl">
      <div data-voodbuilder-dropzone="copy">
        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-vp-brand-2">Featured</p>
        <h1 class="mb-5 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">Tell your story on a full-bleed canvas</h1>
        <p class="max-w-xl text-lg leading-relaxed text-zinc-200">Drop headings, copy, and buttons into the content area. The background image stays behind your message in the page builder and on the live site.</p>
      </div>
      <div class="mt-8 flex min-h-12 flex-wrap items-center gap-4" data-voodbuilder-dropzone="actions">
        <a href="#" class="inline-flex items-center gap-2 rounded-lg bg-vp-brand-1 px-6 py-3 text-sm font-semibold text-white hover:bg-vp-brand-2">Get started</a>
      </div>
    </div>
  </div>
</section>
HTML;
    }
}
