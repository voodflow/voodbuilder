<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class GrapesJsComponentPageHtml
{
    /**
     * @return list<string>
     */
    public static function componentIds(string $pageHtml): array
    {
        if ($pageHtml === '' || ! str_contains($pageHtml, 'data-voodbuilder-component')) {
            return [];
        }

        if (! preg_match_all('/\bdata-voodbuilder-component=(["\'])([^"\']+)\1/i', $pageHtml, $matches)) {
            return [];
        }

        return array_values(array_unique(array_filter($matches[2])));
    }

    /**
     * Page HTML for page-level Tailwind compile/checks (no component instances).
     */
    public static function htmlForPageTailwindCompile(string $pageHtml): string
    {
        return self::htmlExcludingComponentInstances($pageHtml);
    }

    /**
     * Page HTML without embedded site header/footer chrome (styled by theme.css).
     */
    public static function htmlExcludingSiteChrome(string $pageHtml): string
    {
        if ($pageHtml === '' || ! str_contains($pageHtml, 'data-voodbuilder-gjs-site-header')) {
            return $pageHtml;
        }

        $document = self::loadDocument($pageHtml);
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-voodbuilder-gjs-site-header]');

        if ($nodes === false) {
            return $pageHtml;
        }

        $toRemove = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $toRemove[] = $node;
            }
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        return self::serializeBodyChildren($document);
    }

    /**
     * Page HTML without component instance subtrees (for page-level Tailwind CSS checks/compile).
     */
    public static function htmlExcludingComponentInstances(string $pageHtml): string
    {
        if ($pageHtml === '' || ! str_contains($pageHtml, 'data-voodbuilder-component')) {
            return $pageHtml;
        }

        $document = self::loadDocument($pageHtml);
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-voodbuilder-component]');

        if ($nodes === false) {
            return $pageHtml;
        }

        $toRemove = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $toRemove[] = $node;
            }
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        return self::serializeBodyChildren($document);
    }

    protected static function serializeBodyChildren(DOMDocument $document): string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return '';
        }

        $html = '';

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $html .= $document->saveHTML($child);
            }
        }

        return trim($html);
    }

    public static function instanceInnerHtml(string $pageHtml, string $componentId): ?string
    {
        if ($pageHtml === '' || $componentId === '') {
            return null;
        }

        $document = self::loadDocument($pageHtml);
        $xpath = new DOMXPath($document);
        $escapedId = self::escapeXPathLiteral($componentId);
        $nodes = $xpath->query('//*[@data-voodbuilder-component='.$escapedId.']');

        if ($nodes === false) {
            return null;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $innerHtml = self::serializeElementChildren($document, $node);

            if ($innerHtml !== '') {
                return $innerHtml;
            }
        }

        return null;
    }

    protected static function serializeElementChildren(DOMDocument $document, DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $html .= $document->saveHTML($child);
            }
        }

        return trim($html);
    }

    protected static function loadDocument(string $html): DOMDocument
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

    protected static function escapeXPathLiteral(string $value): string
    {
        if (! str_contains($value, '"')) {
            return '"'.$value.'"';
        }

        if (! str_contains($value, "'")) {
            return "'".$value."'";
        }

        $parts = explode('"', $value);

        return 'concat("'.implode('", \'"\', "', $parts).'")';
    }
}
