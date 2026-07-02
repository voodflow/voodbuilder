<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use Voodflow\Voodbuilder\Models\BuilderComponent;
use Voodflow\Voodbuilder\Models\SitePage;

final class GrapesJsComponentRenderer
{
    public function render(string $html, ?SitePage $page = null): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-component')) {
            return $html;
        }

        $document = $this->loadDocument($html);

        foreach ($this->componentElements($document) as $element) {
            $this->expandInstance($element, $document);
        }

        return $this->extractBodyHtml($document) ?? $html;
    }

    protected function expandInstance(DOMElement $element, DOMDocument $document): void
    {
        $componentId = trim($element->getAttribute('data-voodbuilder-component'));

        if ($componentId === '') {
            return;
        }

        if ($this->instanceHasStoredContent($element)) {
            $this->stripInstanceWrapperAttributes($element);

            return;
        }

        $component = BuilderComponent::query()->find($componentId);

        if ($component === null) {
            return;
        }

        $props = $this->parseProps($element->getAttribute('data-voodbuilder-component-props'));
        $componentHtml = TailblocksThemeTokenMigrator::migrateHtml((string) $component->html);
        $template = $this->loadDocument($componentHtml);

        $body = $template->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return;
        }

        $fragmentHtml = '';

        foreach ($body->childNodes as $child) {
            $fragmentHtml .= $template->saveHTML($child);
        }

        $fragmentDoc = $this->loadDocument($fragmentHtml);
        $this->applyProps($fragmentDoc, $props, $component->propertySchema());

        $expandedHtml = $this->extractBodyHtml($fragmentDoc) ?? '';

        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }

        $wrapper = $this->loadDocument($expandedHtml);
        $wrapperBody = $wrapper->getElementsByTagName('body')->item(0);

        if ($wrapperBody === null) {
            return;
        }

        foreach ($wrapperBody->childNodes as $child) {
            $element->appendChild($document->importNode($child, true));
        }

        $this->stripInstanceWrapperAttributes($element);
    }

    protected function instanceHasStoredContent(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return true;
            }
        }

        return false;
    }

    protected function stripInstanceWrapperAttributes(DOMElement $element): void
    {
        $componentId = trim($element->getAttribute('data-voodbuilder-component'));

        if ($componentId !== '') {
            $element->setAttribute(GrapesJsComponentInstanceCssScoper::SCOPE_ATTR, $componentId);
        }

        $element->removeAttribute('data-voodbuilder-component');
        $element->removeAttribute('data-voodbuilder-component-props');
        $this->markComponentRendered($element);
    }

    protected function markComponentRendered(DOMElement $element): void
    {
        $class = trim(preg_replace('/\bvoodbuilder-gjs-component-instance\b/', '', $element->getAttribute('class')) ?? '');
        $class = trim($class.' voodbuilder-component-rendered');

        $element->setAttribute('class', preg_replace('/\s+/', ' ', $class) ?? $class);
    }

    /**
     * @param  list<array{id?: string, type?: string, default?: mixed}>  $schema
     * @param  array<string, mixed>  $props
     */
    protected function applyProps(DOMDocument $document, array $props, array $schema): void
    {
        $defaults = [];

        foreach ($schema as $property) {
            $id = (string) ($property['id'] ?? '');

            if ($id !== '') {
                $defaults[$id] = $property['default'] ?? '';
            }
        }

        $resolved = array_merge($defaults, $props);

        foreach ($document->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement || ! $node->hasAttribute('data-voodbuilder-prop')) {
                continue;
            }

            $propId = trim($node->getAttribute('data-voodbuilder-prop'));
            $value = (string) ($resolved[$propId] ?? '');
            $type = $this->propertyType($schema, $propId);

            $this->applyPropValue($node, $type, $value);
            $node->removeAttribute('data-voodbuilder-prop');
        }
    }

    /**
     * @param  list<array{id?: string, type?: string}>  $schema
     */
    protected function propertyType(array $schema, string $propId): string
    {
        foreach ($schema as $property) {
            if (($property['id'] ?? '') === $propId) {
                return (string) ($property['type'] ?? 'text');
            }
        }

        return 'text';
    }

    protected function applyPropValue(DOMElement $element, string $type, string $value): void
    {
        $tag = strtolower($element->tagName);

        if ($type === 'image' && $tag === 'img') {
            $element->setAttribute('src', $value);

            return;
        }

        if ($type === 'url' && $tag === 'a') {
            $element->setAttribute('href', $value);

            return;
        }

        if ($type === 'url' && $tag === 'button') {
            $element->textContent = $value;

            return;
        }

        if ($type === 'rich_text') {
            while ($element->firstChild !== null) {
                $element->removeChild($element->firstChild);
            }

            $fragment = $element->ownerDocument?->createDocumentFragment();

            if ($fragment !== null) {
                $previous = libxml_use_internal_errors(true);
                $fragment->appendXML($value);
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
                $element->appendChild($fragment);
            }

            return;
        }

        $element->textContent = $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseProps(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

        return is_array($decoded) ? $decoded : [];
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
    protected function componentElements(DOMDocument $document): array
    {
        $elements = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-component')) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    protected function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return $html;
    }
}
