<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Produces portable catalog HTML for component JSON export (no page-instance shell or editor artifacts).
 */
final class GrapesJsComponentExportNormalizer
{
    /**
     * @var list<string>
     */
    private const REMOVED_ATTRIBUTES = [
        'data-voodbuilder-component',
        'data-voodbuilder-component-props',
        'data-vb-component-id',
        'data-gjs-type',
        'data-gjs-highlightable',
        'data-gjs-editable',
        'data-gjs-droppable',
        'data-gjs-draggable',
        'data-gjs-removable',
        'data-gjs-copyable',
        'data-gjs-layerable',
        'data-gjs-selectable',
        'data-gjs-hoverable',
        'data-gjs-badgable',
        'data-highlightable',
        'ratiodefault',
        'contenteditable',
    ];

    public static function htmlForExport(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $html = self::unwrapInstanceShell($html);
        $html = self::sanitizeEditorArtifacts($html);
        $html = GrapesJsImportedTailwindSupport::stripSpuriousSvgBakedPaint($html);
        $html = self::normalizeSvgViewBoxAttribute($html);
        $html = GrapesJsHtmlSanitizer::sanitize($html);

        return trim($html);
    }

    protected static function unwrapInstanceShell(string $html): string
    {
        if (! str_contains($html, 'voodbuilder-gjs-component-instance')
            && ! str_contains($html, 'data-voodbuilder-component')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return $html;
        }

        $rootElements = [];

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $rootElements[] = $child;
            }
        }

        if (count($rootElements) !== 1) {
            return $html;
        }

        $instance = $rootElements[0];

        if (! self::isInstanceShell($instance)) {
            return $html;
        }

        $xpath = new DOMXPath($document);
        $pastedNodes = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " voodbuilder-pasted-component ")]', $instance);

        if ($pastedNodes !== false && $pastedNodes->length === 1) {
            $pasted = $pastedNodes->item(0);

            if ($pasted instanceof DOMElement) {
                return self::serializeElement($document, $pasted);
            }
        }

        return self::serializeElementChildren($document, $instance);
    }

    protected static function sanitizeEditorArtifacts(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $document = self::loadDocument($html);
        $xpath = new DOMXPath($document);
        $elements = $xpath->query('//*');

        if ($elements === false) {
            return $html;
        }

        /** @var array<string, string> $idMap */
        $idMap = [];

        foreach ($elements as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            foreach (self::REMOVED_ATTRIBUTES as $attribute) {
                $element->removeAttribute($attribute);
            }

            if ($element->hasAttribute('id')) {
                $original = trim($element->getAttribute('id'));

                if ($original !== '') {
                    $normalized = self::stripEditorId($original);

                    if ($normalized !== $original) {
                        $idMap[$original] = $normalized;
                        $element->setAttribute('id', $normalized);
                    }
                }
            }

            if ($element->tagName === 'svg' && $element->hasAttribute('viewbox')) {
                $viewBox = $element->getAttribute('viewbox');
                $element->removeAttribute('viewbox');
                $element->setAttribute('viewBox', $viewBox);
            }

            self::sanitizeClassAttribute($element);
        }

        if ($idMap !== []) {
            self::rewriteIdReferences($document, $idMap);
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    /**
     * @param  array<string, string>  $idMap
     */
    protected static function rewriteIdReferences(DOMDocument $document, array $idMap): void
    {
        $xpath = new DOMXPath($document);
        $elements = $xpath->query('//*');

        if ($elements === false) {
            return;
        }

        foreach ($elements as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            if ($element->hasAttributes()) {
                foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
                    $value = $attribute->value;

                    foreach ($idMap as $from => $to) {
                        $value = str_replace('#'.$from, '#'.$to, $value);
                        $value = str_replace('url(#'.$from.')', 'url(#'.$to.')', $value);
                    }

                    if ($value !== $attribute->value) {
                        $element->setAttribute($attribute->name, $value);
                    }
                }
            }
        }
    }

    protected static function sanitizeClassAttribute(DOMElement $element): void
    {
        if (! $element->hasAttribute('class')) {
            return;
        }

        $classes = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];
        $classes = array_values(array_filter(
            $classes,
            static fn (string $className): bool => $className !== ''
                && $className !== 'voodbuilder-gjs-component-instance'
                && ! str_starts_with($className, 'gjs-')
                && ! preg_match('/^c[0-9a-f]{4,8}$/i', $className),
        ));

        if ($classes === []) {
            $element->removeAttribute('class');
        } else {
            $element->setAttribute('class', implode(' ', $classes));
        }
    }

    protected static function stripEditorId(string $id): string
    {
        $stripped = preg_replace('/-vb-[a-f0-9]+/i', '', $id) ?? $id;
        $stripped = preg_replace('/-\d+$/', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/-+$/', '', $stripped) ?? $stripped;

        return $stripped !== '' ? $stripped : $id;
    }

    protected static function normalizeSvgViewBoxAttribute(string $html): string
    {
        return preg_replace('/\sviewbox=(["\'])/i', ' viewBox=$1', $html) ?? $html;
    }

    protected static function isInstanceShell(DOMElement $element): bool
    {
        if ($element->hasAttribute('data-voodbuilder-component')) {
            return true;
        }

        $class = $element->getAttribute('class');

        return $class !== '' && str_contains($class, 'voodbuilder-gjs-component-instance');
    }

    protected static function serializeElement(DOMDocument $document, DOMElement $element): string
    {
        return trim($document->saveHTML($element));
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

    protected static function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return null;
        }

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return trim($html);
    }
}
