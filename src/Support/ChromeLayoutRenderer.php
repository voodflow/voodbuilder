<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use DOMDocument;
use DOMElement;
use Voodflow\Voodbuilder\Licensing\AuthorScriptPolicy;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\Editor\EditorChromeHtmlPipeline;
use Voodflow\Voodbuilder\Support\Editor\EditorPastedComponentNormalizer;
use Voodflow\Voodbuilder\Support\Editor\EditorSlotHydrator;

/**
 * Splits a chrome layout around the content slot and hydrates menus/branding.
 */
final class ChromeLayoutRenderer
{
    /**
     * @return array{before: string, after: string, css: string, js: string}
     */
    public function render(ChromeLayout $layout, bool $canvasPreview = false): array
    {
        $payload = $layout->builderPayload();
        $html = EditorChromeHtmlPipeline::render($payload['html'], $canvasPreview);
        $html = EditorSlotHydrator::hydrateHtml($html);

        return [
            ...$this->splitAroundContentSlot($html),
            'css' => ChromeLayoutCssScoper::scope(
                EditorPastedComponentNormalizer::dedupeCssRules(
                    ThemePalette::stripEmbeddedPaletteOverrides(trim($payload['css'])),
                ),
            ),
            // Chrome layouts have their own `js` column, and chrome-app.blade.php drops it
            // into a <script> on every public page — so it is the same arbitrary-code
            // channel as a page's own script and answers to the same policy. It used to
            // bypass it entirely, which made the header the way to run JS without the
            // capability the page editor asks for.
            'js' => trim(AuthorScriptPolicy::keepOrDiscard($payload['js'])),
        ];
    }

    /**
     * @return array{before: string, after: string}
     */
    public function splitAroundContentSlot(string $html): array
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-content-slot')) {
            return [
                'before' => $html,
                'after' => '',
            ];
        }

        $document = $this->loadDocument($html);
        $slot = $this->findContentSlot($document);

        if ($slot === null) {
            return [
                'before' => $html,
                'after' => '',
            ];
        }

        $before = '';
        $after = '';
        $seenSlot = false;

        foreach ($slot->parentNode?->childNodes ?? [] as $child) {
            if ($child === $slot) {
                $seenSlot = true;

                continue;
            }

            $chunk = $document->saveHTML($child);

            if ($seenSlot) {
                $after .= $chunk;
            } else {
                $before .= $chunk;
            }
        }

        return [
            'before' => $before,
            'after' => $after,
        ];
    }

    protected function loadDocument(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    protected function findContentSlot(DOMDocument $document): ?DOMElement
    {
        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-content-slot')) {
                return $element;
            }
        }

        return null;
    }
}
