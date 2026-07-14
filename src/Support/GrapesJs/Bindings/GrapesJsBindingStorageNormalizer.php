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
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-bind')) {
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
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-bind')) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    protected function resetElementForStorage(DOMElement $element): void
    {
        $bindingKey = trim($element->getAttribute('data-voodbuilder-bind'));
        $parsed = BindingKey::tryParse($bindingKey, $this->registry);

        if ($parsed === null) {
            return;
        }

        $field = $this->registry->field($parsed->sourceId, $parsed->fieldId);

        if ($field === null) {
            return;
        }

        $source = $this->registry->source($parsed->sourceId);
        $sourceLabel = $source?->label() ?? 'Dynamic';
        $tag = strtolower($element->tagName);

        match ($field->type) {
            BindingField::TYPE_IMAGE => $this->resetImageElement($element),
            BindingField::TYPE_URL => $this->resetUrlElement($element, $tag),
            default => $this->resetTextElement($element, $sourceLabel, $field->label, $tag),
        };
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

            return;
        }

        if ($tag === 'button') {
            $element->removeAttribute('onclick');
        }
    }

    protected function resetTextElement(DOMElement $element, string $sourceLabel, string $fieldLabel, string $tag): void
    {
        if ($tag === 'button' || $element->getAttribute('data-voodbuilder-cta') === 'true') {
            return;
        }

        if ($tag === 'img') {
            $element->setAttribute('alt', BindingPlaceholders::text($sourceLabel, $fieldLabel));

            return;
        }

        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }

        $element->appendChild($element->ownerDocument->createTextNode(
            BindingPlaceholders::text($sourceLabel, $fieldLabel),
        ));
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
