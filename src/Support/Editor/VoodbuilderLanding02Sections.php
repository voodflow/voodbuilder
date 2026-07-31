<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Community landing section retained in Core (FAQ accordion).
 * Companion sections live in Elements.
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
                'id' => 'vb-landing02-faq',
                'label' => 'FAQ accordion',
                'category' => 'Content',
                'content' => self::faq(),
            ],
        ];
    }

    public static function pageHtml(): string
    {
        return self::faq();
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
