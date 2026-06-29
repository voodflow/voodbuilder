<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs\Bindings;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Vpress\Models\SitePage;

final class GrapesJsRepeatRenderer
{
    public function __construct(
        private readonly ModelIntegrationListResolver $lists,
    ) {}

    public function render(string $html, ?SitePage $page = null): string
    {
        if ($html === '' || ! self::containsRepeatAttribute($html)) {
            return $html;
        }

        $document = $this->loadDocument($html);

        foreach ($this->repeatContainers($document) as $container) {
            $this->expandRepeat($container, $page);
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
    protected function repeatContainers(DOMDocument $document): array
    {
        $elements = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-vpress-repeat')) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    protected function expandRepeat(DOMElement $container, ?SitePage $page): void
    {
        $repeatKey = trim($container->getAttribute('data-vpress-repeat'));

        if ($repeatKey === '') {
            return;
        }

        $limit = (int) ($container->getAttribute('data-vpress-repeat-limit') ?: 6);
        $sort = $container->getAttribute('data-vpress-repeat-sort') ?: null;
        $sortDir = $container->getAttribute('data-vpress-repeat-sort-dir') ?: null;
        $records = $this->lists->resolve($repeatKey, $limit, $sort ?: null, $sortDir ?: null);

        if ($records === []) {
            return;
        }

        $template = $this->resolveTemplateNode($container);

        if (! $template instanceof DOMElement) {
            return;
        }

        $templateHtml = $container->ownerDocument?->saveHTML($template);

        if ($templateHtml === false || $templateHtml === '') {
            return;
        }

        $insertHost = $template->parentNode instanceof DOMElement
            ? $template->parentNode
            : $container;
        $insertBefore = $template->nextSibling;

        foreach ($records as $record) {
            if (! $record instanceof Model) {
                continue;
            }

            $fragmentHtml = app(GrapesJsBindingRenderer::class)->render(
                $templateHtml,
                $page,
                $record,
            );

            $this->appendHtmlNodes($insertHost, $fragmentHtml, $insertBefore);
        }

        $insertHost->removeChild($template);
        $container->removeAttribute('data-vpress-repeat');
        $container->removeAttribute('data-vpress-repeat-limit');
        $container->removeAttribute('data-vpress-repeat-sort');
        $container->removeAttribute('data-vpress-repeat-sort-dir');

        if ($insertHost !== $container && $insertHost instanceof DOMElement) {
            $insertHost->removeAttribute('data-vpress-repeat');
            $insertHost->removeAttribute('data-vpress-repeat-limit');
            $insertHost->removeAttribute('data-vpress-repeat-sort');
            $insertHost->removeAttribute('data-vpress-repeat-sort-dir');
            $insertHost->removeAttribute('data-vpress-repeat-item');
        }
    }

    protected function resolveTemplateNode(DOMElement $container): ?DOMElement
    {
        foreach ($container->childNodes as $child) {
            if ($child instanceof DOMElement && $child->hasAttribute('data-vpress-repeat-item')) {
                return $this->normalizeRepeatTemplateNode($child);
            }
        }

        foreach ($container->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return $this->normalizeRepeatTemplateNode($child);
            }
        }

        return null;
    }

    protected function normalizeRepeatTemplateNode(DOMElement $node): DOMElement
    {
        $class = $node->getAttribute('class');

        if (str_contains($class, 'flex-wrap') || str_contains($class, 'grid')) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    return $child;
                }
            }
        }

        return $node;
    }

    protected function appendHtmlNodes(DOMElement $container, string $html, ?DOMNode $insertBefore): void
    {
        $document = $container->ownerDocument;

        if ($document === null) {
            return;
        }

        $wrapper = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $wrapper->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $wrapper->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return;
        }

        while ($body->firstChild !== null) {
            $child = $body->firstChild;
            $node = $document->importNode($child, true);
            $body->removeChild($child);

            if ($insertBefore !== null && $insertBefore->parentNode === $container) {
                $container->insertBefore($node, $insertBefore);
            } else {
                $container->appendChild($node);
            }
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

    private static function containsRepeatAttribute(string $html): bool
    {
        return (bool) preg_match('/\bdata-vpress-repeat\s*=/', $html);
    }
}
