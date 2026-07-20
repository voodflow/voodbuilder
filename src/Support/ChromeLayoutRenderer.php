<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use DOMDocument;
use DOMElement;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsChromeHtmlPipeline;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsSlotHydrator;

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
        $html = GrapesJsChromeHtmlPipeline::render($payload['html'], $canvasPreview);
        $html = GrapesJsSlotHydrator::hydrateHtml($html);

        return [
            ...$this->splitAroundContentSlot($html),
            'css' => GrapesJsPastedComponentNormalizer::dedupeCssRules(
                ThemePalette::stripEmbeddedPaletteOverrides(trim($payload['css'])),
            ),
            'js' => trim($payload['js']),
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
