<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Popups;

final class GrapesJsPopupHtmlNormalizer
{
    public static function normalize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        if (! str_contains(strtolower($html), '<body') && ! str_contains($html, '<')) {
            return $html;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $wrapped = str_contains(strtolower($html), '<body')
            ? $html
            : '<?xml encoding="UTF-8"><body>'.$html.'</body>';

        $document->loadHTML(
            $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof \DOMElement) {
            return $html;
        }

        self::removeEmptyContainers($body);

        $inner = '';

        foreach ($body->childNodes as $child) {
            $inner .= $document->saveHTML($child);
        }

        return trim($inner);
    }

    private static function removeEmptyContainers(\DOMElement $root): void
    {
        $xpath = new \DOMXPath($root->ownerDocument);
        $nodes = $xpath->query('.//*[self::div or self::section][not(normalize-space()) and not(.//*[@src or @href or self::img or self::input or self::textarea or self::select or self::br or self::hr or self::svg])]', $root);

        if (! $nodes instanceof \DOMNodeList) {
            return;
        }

        /** @var list<\DOMElement> $toRemove */
        $toRemove = [];

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $toRemove[] = $node;
            }
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }
    }
}
