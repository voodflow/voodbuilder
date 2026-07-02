<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use Voodflow\Voodbuilder\Models\BuilderComponent;

final class GrapesJsComponentCssRenderer
{
    public function cssForHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-component')) {
            return '';
        }

        if (! preg_match_all('/\bdata-voodbuilder-component=(["\'])([^"\']+)\1/i', $html, $matches)) {
            return '';
        }

        $ids = array_values(array_unique(array_filter($matches[2])));

        if ($ids === []) {
            return '';
        }

        $instanceHtmlById = $this->instanceHtmlByComponentId($html);

        return BuilderComponent::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->map(function (BuilderComponent $component) use ($instanceHtmlById): string {
                $instanceHtml = $instanceHtmlById[(string) $component->id] ?? '';
                $catalogHtml = TailblocksThemeTokenMigrator::migrateHtml((string) $component->html);
                $sourceHtml = $instanceHtml !== '' ? $instanceHtml : $catalogHtml;

                return GrapesJsComponentInstanceCssScoper::scopeCssToComponentInstance(
                    GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($sourceHtml, $component->css),
                    (string) $component->id,
                );
            })
            ->filter(fn (string $css): bool => $css !== '')
            ->implode("\n\n");
    }

    /**
     * @return array<string, string>
     */
    protected function instanceHtmlByComponentId(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $byId = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-voodbuilder-component')) {
                continue;
            }

            $id = trim($element->getAttribute('data-voodbuilder-component'));

            if ($id === '') {
                continue;
            }

            $inner = '';

            foreach ($element->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    $inner .= $document->saveHTML($child);
                }
            }

            if ($inner !== '') {
                $byId[$id] = $inner;
            }
        }

        return $byId;
    }
}
