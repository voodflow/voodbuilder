<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use DOMDocument;
use DOMElement;

final class GrapesJsBindingStorageNormalizer
{
    public function __construct(
        private readonly BindingRegistry $registry,
    ) {}

    public function normalizeHtml(string $html): string
    {
        if (
            $html === ''
            || (
                ! str_contains($html, BindingAttributes::BIND)
                && ! str_contains($html, BindingAttributes::BIND_HREF)
            )
        ) {
            return $html;
        }

        $document = $this->loadDocument($html);

        foreach ($this->boundElements($document) as $element) {
            $this->resetElementForStorage($element);
        }

        return $this->extractBodyHtml($document) ?? $html;
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

    /**
     * @return list<DOMElement>
     */
    protected function boundElements(DOMDocument $document): array
    {
        $elements = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            if (
                $element->hasAttribute(BindingAttributes::BIND)
                || $element->hasAttribute(BindingAttributes::BIND_HREF)
            ) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    protected function resetElementForStorage(DOMElement $element): void
    {
        $bindingKey = trim($element->getAttribute(BindingAttributes::BIND));

        if ($bindingKey !== '') {
            $parsed = BindingKey::tryParse($bindingKey, $this->registry);

            if ($parsed !== null) {
                $field = $this->registry->field($parsed->sourceId, $parsed->fieldId);

                if ($field !== null) {
                    $source = $this->registry->source($parsed->sourceId);
                    $sourceLabel = $source?->label() ?? 'Dynamic';
                    $tag = strtolower($element->tagName);

                    match ($field->type) {
                        BindingField::TYPE_IMAGE => $this->resetImageElement($element),
                        BindingField::TYPE_URL => $this->resetUrlElement($element, $tag),
                        default => $this->resetTextElement($element, $sourceLabel, $field->label, $tag),
                    };
                }
            }
        }

        if (trim($element->getAttribute(BindingAttributes::BIND_HREF)) !== '') {
            $this->resetUrlElement($element, strtolower($element->tagName));
        }
    }

    protected function resetImageElement(DOMElement $element): void
    {
        if (strtolower($element->tagName) !== 'img') {
            return;
        }

        $element->setAttribute('src', BindingPlaceholders::imageDataUri());

        $currentAlt = trim($element->getAttribute('alt'));

        if ($currentAlt === '' || BindingImageAltResolver::isPlaceholderAlt($currentAlt)) {
            $element->setAttribute('alt', '');
        }
    }

    protected function resetUrlElement(DOMElement $element, string $tag): void
    {
        if ($tag === 'a') {
            $element->setAttribute('href', '#');
            $this->stripSpuriousDirectTextNodes($element);

            return;
        }

        if ($tag === 'button') {
            $element->removeAttribute('onclick');
        }
    }

    /**
     * Card-style URL bindings only set href. Remove direct text nodes scraped
     * from nested title/category copy in the editor.
     */
    protected function stripSpuriousDirectTextNodes(DOMElement $element): void
    {
        $hasElementChild = false;

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $hasElementChild = true;

                break;
            }
        }

        if (! $hasElementChild) {
            return;
        }

        $toRemove = [];

        foreach ($element->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE && trim((string) $child->textContent) !== '') {
                $toRemove[] = $child;
            }
        }

        foreach ($toRemove as $node) {
            $element->removeChild($node);
        }

        // Keep CTA label attr when the label itself is dynamically bound.
        if (
            $element->hasAttribute('data-voodbuilder-cta-label')
            && trim($element->getAttribute(BindingAttributes::BIND)) === ''
        ) {
            $element->removeAttribute('data-voodbuilder-cta-label');
        }
    }

    protected function resetTextElement(DOMElement $element, string $sourceLabel, string $fieldLabel, string $tag): void
    {
        if ($tag === 'img') {
            $element->setAttribute('alt', BindingPlaceholders::text($sourceLabel, $fieldLabel));

            return;
        }

        $placeholder = BindingPlaceholders::text($sourceLabel, $fieldLabel);

        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }

        $element->appendChild($element->ownerDocument->createTextNode($placeholder));

        if ($tag === 'button' || $element->getAttribute('data-voodbuilder-cta') === 'true') {
            $element->setAttribute('data-voodbuilder-cta-label', $placeholder);
        }
    }

    protected function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
