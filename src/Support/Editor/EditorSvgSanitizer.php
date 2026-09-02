<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Make an uploaded SVG safe to serve from the public disk.
 *
 * An SVG is a document, not an image: opened directly it executes its own script in the
 * site's origin. The core upload fallback accepts SVG and stores it on a public disk
 * without the checks vmedia applies, so uploads are cleaned here instead of rejected —
 * logos are the common case and blocking them would break existing sites.
 */
final class EditorSvgSanitizer
{
    private const DROPPED_ELEMENTS = [
        'script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video',
        'animate', 'animatetransform', 'set', 'handler', 'listener',
    ];

    /** Only these may carry a URL, and only to safe targets. */
    private const URL_ATTRIBUTES = ['href', 'xlink:href', 'src', 'from', 'to', 'values'];

    public static function sanitize(string $svg): string
    {
        if (trim($svg) === '') {
            return $svg;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        // No LIBXML_NOENT: external entity expansion is an XXE vector.
        $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $document->documentElement instanceof DOMElement) {
            // Unparseable input is not something we want on a public disk.
            return '';
        }

        self::stripDoctype($document);
        self::walk($document->documentElement);

        return (string) $document->saveXML();
    }

    /**
     * A DOCTYPE is only ever used here to declare entities.
     */
    private static function stripDoctype(DOMDocument $document): void
    {
        if ($document->doctype !== null) {
            $document->removeChild($document->doctype);
        }
    }

    private static function walk(DOMNode $node): void
    {
        if ($node instanceof DOMElement) {
            self::sanitizeAttributes($node);
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->localName ?? $child->nodeName), self::DROPPED_ELEMENTS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::walk($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! $attribute instanceof DOMAttr) {
                continue;
            }

            $name = strtolower($attribute->name);

            if (str_starts_with($name, 'on')) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if (in_array($name, self::URL_ATTRIBUTES, true) && ! self::isSafeUrl($attribute->value)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if ($name === 'style' && self::styleIsDangerous($attribute->value)) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private static function isSafeUrl(string $value): bool
    {
        $normalized = strtolower((string) preg_replace('/[\s\x00-\x1F\x7F]+/', '', $value));

        if ($normalized === '' || ! str_contains($normalized, ':')) {
            return true;
        }

        // Fragment references (`#gradient-1`) are how SVG links its own defs.
        if (str_starts_with($normalized, '#')) {
            return true;
        }

        return str_starts_with($normalized, 'https:')
            || str_starts_with($normalized, 'http:')
            || preg_match('~^data:image/(?:png|jpe?g|gif|webp)[;,]~', $normalized) === 1;
    }

    private static function styleIsDangerous(string $value): bool
    {
        $normalized = strtolower((string) preg_replace('/[\s\x00-\x1F\x7F]+/', '', $value));

        foreach (['expression(', 'javascript:', 'vbscript:', 'behavior:', '-moz-binding', '@import'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
