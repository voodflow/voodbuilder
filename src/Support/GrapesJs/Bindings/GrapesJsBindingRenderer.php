<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use DOMDocument;
use DOMElement;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHtmlSanitizer;

final class GrapesJsBindingRenderer
{
    public function __construct(
        private readonly BindingRegistry $registry,
    ) {}

    public function render(string $html, ?SitePage $page = null, mixed $repeatItem = null): string
    {
        if ($html === '' || (! str_contains($html, 'data-voodbuilder-bind') && ! self::containsRepeatAttribute($html))) {
            return $html;
        }

        if ($repeatItem === null && self::containsRepeatAttribute($html)) {
            $html = app(GrapesJsRepeatRenderer::class)->render($html, $page);
        }

        if (! str_contains($html, 'data-voodbuilder-bind')) {
            return $html;
        }

        $context = BindingContext::forPage($page, $repeatItem);
        $document = $this->loadDocument($html);

        foreach ($this->boundElements($document) as $element) {
            $this->applyBinding($element, $context);
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

    protected function applyBinding(DOMElement $element, BindingContext $context): void
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

        $value = $this->registry->resolve($bindingKey, $context);

        if ($value === null || $value === '') {
            return;
        }

        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tag = strtolower($element->tagName);

        match ($field->type) {
            BindingField::TYPE_IMAGE => $this->applyImageBinding($element, $escaped, $bindingKey, $context),
            BindingField::TYPE_URL => $this->applyUrlBinding($element, $escaped),
            default => $this->applyTextBinding($element, $escaped, $tag),
        };
    }

    protected function applyImageBinding(DOMElement $element, string $url, string $bindingKey, BindingContext $context): void
    {
        if (strtolower($element->tagName) !== 'img') {
            return;
        }

        $element->setAttribute('src', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $alt = $this->imageAltResolver()->resolve($bindingKey, $context);

        if ($alt !== null && $alt !== '') {
            $element->setAttribute('alt', htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return;
        }

        $currentAlt = trim($element->getAttribute('alt'));

        if ($currentAlt === '' || BindingImageAltResolver::isPlaceholderAlt($currentAlt)) {
            $element->setAttribute('alt', '');
        }
    }

    protected function imageAltResolver(): BindingImageAltResolver
    {
        return new BindingImageAltResolver($this->registry);
    }

    protected function applyUrlBinding(DOMElement $element, string $url): void
    {
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $tag = strtolower($element->tagName);

        if ($tag === 'a') {
            $element->setAttribute('href', $decoded);
            $this->stripSpuriousDirectTextNodes($element);

            return;
        }

        if ($tag === 'button') {
            $element->setAttribute(
                'onclick',
                'window.location.href='.json_encode($decoded, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            );
        }
    }

    /**
     * Card-style URL bindings only set href. Remove direct text nodes that were
     * accidentally scraped from nested title/category copy in the editor.
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

        if ($element->hasAttribute('data-voodbuilder-cta-label')) {
            $element->removeAttribute('data-voodbuilder-cta-label');
        }
    }

    protected function applyTextBinding(DOMElement $element, string $text, string $tag): void
    {
        if ($tag === 'button' || $element->getAttribute('data-voodbuilder-cta') === 'true') {
            return;
        }

        if ($tag === 'img') {
            $element->setAttribute('alt', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return;
        }

        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($this->isAnimatedCounterElement($element)) {
            GrapesJsHtmlSanitizer::syncCounterElementFromValue($element, $decoded);

            return;
        }

        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }

        $element->appendChild($element->ownerDocument->createTextNode($decoded));
    }

    protected function isAnimatedCounterElement(DOMElement $element): bool
    {
        if ($element->hasAttribute('data-voodbuilder-animated-counter')
            || $element->hasAttribute('data-vb-count-to')) {
            return true;
        }

        $class = ' '.$element->getAttribute('class').' ';

        return str_contains($class, ' vb-animated-counter ');
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

    private static function containsRepeatAttribute(string $html): bool
    {
        return (bool) preg_match('/\bdata-voodbuilder-repeat\s*=/', $html);
    }
}
