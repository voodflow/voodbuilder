<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Strip the executable surface out of builder HTML, over a real DOM parse.
 *
 * This deliberately does not use `symfony/html-sanitizer`: that library allowlists attributes
 * per element with no wildcard support, and the block contract is built on ~90 custom
 * `data-voodbuilder-*` / `data-vforms-*` / `data-vb-*` attributes that companions extend
 * without the core knowing their names. An attribute allowlist would silently delete
 * companion markup on every save. Use `EditorUntrustedHtmlSanitizer` for HTML that comes
 * from outside the installation (remote templates, remote elements), where a strict
 * allowlist is the right trade-off.
 *
 * The guarantee here is narrower but enforced structurally rather than by regex: no script
 * execution, no dangling event handlers, no URL scheme that can run code.
 */
final class EditorHtmlSecuritySanitizer
{
    /**
     * Removed with their subtree. `<style>` is intentionally absent: author CSS is a builder
     * feature, and CSS cannot execute script in any browser we support.
     */
    private const DROPPED_ELEMENTS = [
        'script', 'base', 'object', 'embed', 'applet', 'frame', 'frameset',
        'noframes', 'foreignobject', 'meta', 'link', 'noscript',
    ];

    /** Attributes whose value is a URL and therefore a scheme-injection vector. */
    private const URL_ATTRIBUTES = [
        'href', 'src', 'action', 'formaction', 'poster', 'data', 'background',
        'xlink:href', 'ping', 'cite', 'longdesc', 'dynsrc', 'lowsrc', 'srcset',
    ];

    /** Attributes that embed a whole document and cannot be made safe. */
    private const DROPPED_ATTRIBUTES = ['srcdoc'];

    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel', 'sms', 'callto', 'webcal'];

    /**
     * @param  bool  $allowAuthorScripts  keep `<script>` written by an author who holds the
     *                                    `pages.custom-js` capability; event handlers and unsafe
     *                                    URL schemes are removed either way
     */
    public static function sanitize(string $html, bool $allowAuthorScripts = false): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $document = self::loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return $html;
        }

        self::walk($body, $allowAuthorScripts);

        return self::extractInnerHtml($document, $body);
    }

    private static function walk(DOMNode $node, bool $allowAuthorScripts): void
    {
        // Snapshot: removing a child mutates the live DOMNodeList mid-iteration.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $name = strtolower($child->nodeName);

            if (self::shouldDrop($name, $allowAuthorScripts)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child);
            self::walk($child, $allowAuthorScripts);
        }
    }

    private static function shouldDrop(string $name, bool $allowAuthorScripts): bool
    {
        if ($name === 'script') {
            return ! $allowAuthorScripts;
        }

        return in_array($name, self::DROPPED_ELEMENTS, true);
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! $attribute instanceof DOMAttr) {
                continue;
            }

            $name = strtolower($attribute->name);

            // Inline handlers are the single largest XSS vector and never legitimate output.
            if (str_starts_with($name, 'on')) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, self::DROPPED_ATTRIBUTES, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, self::URL_ATTRIBUTES, true)) {
                self::sanitizeUrlAttribute($element, $attribute, $name);

                continue;
            }

            if ($name === 'style') {
                self::sanitizeStyleAttribute($element, $attribute);
            }
        }
    }

    private static function sanitizeUrlAttribute(DOMElement $element, DOMAttr $attribute, string $name): void
    {
        if (self::isSafeUrl($attribute->value, $name)) {
            return;
        }

        // Anchors keep their shape so layout and styling survive; only the target dies.
        if ($name === 'href') {
            $element->setAttribute($attribute->name, '#');

            return;
        }

        $element->removeAttribute($attribute->name);
    }

    private static function isSafeUrl(string $value, string $attribute): bool
    {
        // DOM parsing already decoded entities, so `&#106;avascript:` arrives as `javascript:`.
        // Strip whitespace and control characters that browsers ignore inside a scheme.
        $normalized = strtolower((string) preg_replace('/[\s\x00-\x1F\x7F]+/', '', $value));

        if ($normalized === '') {
            return true;
        }

        // Relative, root-relative, protocol-relative, anchors and query strings.
        if (! str_contains($normalized, ':') || preg_match('~^(?:/|\./|\.\./|#|\?)~', $normalized) === 1) {
            return true;
        }

        // `data:image/*` is required: the bindings placeholder pipeline emits inline SVG
        // previews. Restricted to media attributes — navigating to a `data:` document is not
        // the same risk as rendering one.
        if (str_starts_with($normalized, 'data:')) {
            return $attribute !== 'href'
                && preg_match('~^data:image/(?:png|jpe?g|gif|webp|avif|svg\+xml)[;,]~', $normalized) === 1;
        }

        foreach (self::SAFE_SCHEMES as $scheme) {
            if (str_starts_with($normalized, $scheme.':')) {
                return true;
            }
        }

        return false;
    }

    private static function sanitizeStyleAttribute(DOMElement $element, DOMAttr $attribute): void
    {
        $normalized = strtolower((string) preg_replace('/[\s\x00-\x1F\x7F]+/', '', $attribute->value));

        $dangerous = ['expression(', 'javascript:', 'vbscript:', 'behavior:', '-moz-binding', '@import'];

        foreach ($dangerous as $needle) {
            if (str_contains($normalized, $needle)) {
                $element->removeAttribute($attribute->name);

                return;
            }
        }
    }

    private static function loadDocument(string $html): DOMDocument
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

    private static function extractInnerHtml(DOMDocument $document, DOMElement $body): string
    {
        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= (string) $document->saveHTML($child);
        }

        return $output;
    }
}
