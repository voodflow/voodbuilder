<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use DOMDocument;
use DOMElement;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterBlocks;
use Voodflow\Voodbuilder\Support\Editor\SiteNavBlocks;

/**
 * Chrome Layout Html Sanitizer.
 */
final class ChromeLayoutHtmlSanitizer
{
    public static function normalizeStoredHtml(string $html): string
    {
        $html = ChromeLayoutEditorPreview::unwrapShellPreview(trim($html));
        $html = self::unwrapDropZones($html);
        $html = self::hoistMisplacedChromeBlocks($html);

        if ($html === '') {
            return $html;
        }

        return self::dedupeFooterBlocks($html);
    }

    /**
     * Footer / reading-progress nested inside a site_nav_* block are destroyed when
     * the nav is Blade-remounted. Lift them to siblings so layout save/load keep them.
     */
    public static function hoistMisplacedChromeBlocks(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-block')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return $html;
        }

        $navNodes = [];

        foreach ($body->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-voodbuilder-block')) {
                continue;
            }

            $blockId = (string) $element->getAttribute('data-voodbuilder-block');

            if (SiteNavBlocks::isNavBlockId($blockId)) {
                $navNodes[] = $element;
            }
        }

        foreach ($navNodes as $nav) {
            $parent = $nav->parentNode;

            if (! $parent instanceof \DOMNode) {
                continue;
            }

            $toHoist = [];

            foreach ($nav->getElementsByTagName('*') as $element) {
                if (! $element instanceof DOMElement) {
                    continue;
                }

                if (self::isHoistableChromeElement($element)) {
                    $toHoist[] = $element;
                }
            }

            foreach ($toHoist as $element) {
                // Skip if already moved (ancestor was hoisted).
                if ($element->parentNode === null || ! $nav->contains($element)) {
                    continue;
                }

                $parent->insertBefore($element, $nav->nextSibling);
            }
        }

        return self::serializeBodyChildren($document);
    }

    protected static function isHoistableChromeElement(DOMElement $element): bool
    {
        if ($element->hasAttribute('data-voodbuilder-block')) {
            $blockId = (string) $element->getAttribute('data-voodbuilder-block');

            return SiteFooterBlocks::isFooterBlockId($blockId)
                || $blockId === 'voodbuilder-reading-progress'
                || str_contains($blockId, 'reading-progress');
        }

        if ($element->hasAttribute('data-voodbuilder-progress') || $element->hasAttribute('data-reading-progress')) {
            return true;
        }

        $class = ' '.trim((string) $element->getAttribute('class')).' ';

        return str_contains($class, ' vb-reading-progress ');
    }

    public static function unwrapDropZones(string $html): string
    {
        if (! str_contains($html, 'data-voodbuilder-chrome-drop-zone')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return $html;
        }

        $zones = [];

        foreach ($body->childNodes as $node) {
            if ($node instanceof DOMElement && $node->hasAttribute('data-voodbuilder-chrome-drop-zone')) {
                $zones[] = $node;
            }
        }

        foreach ($zones as $zone) {
            $parent = $zone->parentNode;

            if (! $parent instanceof \DOMNode) {
                continue;
            }

            while ($zone->firstChild instanceof DOMElement) {
                $parent->insertBefore($zone->firstChild, $zone);
            }

            $parent->removeChild($zone);
        }

        return self::serializeBodyChildren($document);
    }

    public static function dedupeFooterBlocks(string $html): string
    {
        if (! str_contains($html, 'data-voodbuilder-block')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $seenFooter = false;
        $toRemove = [];

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return $html;
        }

        foreach ($body->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if (! $node->hasAttribute('data-voodbuilder-block')) {
                continue;
            }

            $blockId = (string) $node->getAttribute('data-voodbuilder-block');

            if (! SiteFooterBlocks::isFooterBlockId($blockId)) {
                continue;
            }

            if ($seenFooter) {
                $toRemove[] = $node;

                continue;
            }

            $seenFooter = true;
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        return self::serializeBodyChildren($document);
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
}
