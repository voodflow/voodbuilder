<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMNode;
use Voodflow\Vpress\Models\VpressSettings;

final class GrapesJsSlotHydrator
{
    public static function hydrateSubtree(DOMDocument $document, DOMElement $root, bool $preview = false): void
    {
        self::hydrateBrands($document, $root, $preview);
        self::hydrateMenus($document, $root, $preview);
    }

    public static function hydrateHtml(string $html, bool $preview = false): string
    {
        if ($html === '' || (! str_contains($html, 'data-vpress-menu') && ! str_contains($html, 'data-vpress-brand'))) {
            return $html;
        }

        $document = self::loadDocument($html);

        self::hydrateBrands($document, $document->documentElement, $preview);
        self::hydrateMenus($document, $document->documentElement, $preview);

        return self::extractBodyHtml($document) ?? $html;
    }

    public static function renderBrand(bool $preview = false): string
    {
        return view('vpress::grapesjs.blocks.partials.footer-brand', [
            'brandName' => VpressSettings::brandName(),
            'logoUrl' => VpressSettings::logoUrl(),
            'preview' => $preview,
        ])->render();
    }

    public static function renderMenuList(string $menuSlug, bool $preview = false): string
    {
        return view('vpress::grapesjs.blocks.partials.footer-menu-list-wrapper', [
            'menuSlug' => $menuSlug,
            'preview' => $preview,
        ])->render();
    }

    protected static function hydrateBrands(DOMDocument $document, DOMElement $root, bool $preview): void
    {
        foreach ($root->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-vpress-brand')) {
                continue;
            }

            self::replaceElementInnerHtml($document, $element, self::renderBrand($preview));
        }
    }

    protected static function hydrateMenus(DOMDocument $document, DOMElement $root, bool $preview): void
    {
        foreach ($root->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement || ! $element->hasAttribute('data-vpress-menu')) {
                continue;
            }

            $menuSlug = (string) $element->getAttribute('data-vpress-menu');

            if ($menuSlug === '') {
                continue;
            }

            self::replaceElementInnerHtml($document, $element, self::renderMenuList($menuSlug, $preview));
        }
    }

    protected static function replaceElementInnerHtml(DOMDocument $document, DOMElement $element, string $html): void
    {
        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }

        if (trim($html) === '') {
            return;
        }

        $fragmentDocument = self::loadDocument($html);
        $body = $fragmentDocument->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return;
        }

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMNode) {
                $element->appendChild($document->importNode($child, true));
            }
        }
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
