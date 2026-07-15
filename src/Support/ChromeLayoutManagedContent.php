<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterBlocks;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavBlocks;

/**
 * When a chrome layout is assigned to a channel, site nav/footer belong to the layout — not page content.
 */
final class ChromeLayoutManagedContent
{
    public static function chromeLayoutForSitePage(SitePage $page): ?ChromeLayout
    {
        if (filled($page->chrome_layout_id)) {
            $layout = ChromeLayout::query()
                ->where('enabled', true)
                ->find($page->chrome_layout_id);

            if ($layout instanceof ChromeLayout) {
                return $layout;
            }
        }

        return ChromeLayoutResolver::resolveForChannel('pages');
    }

    public static function sitePageUsesChromeShell(SitePage $page): bool
    {
        return self::chromeLayoutForSitePage($page) !== null;
    }

    public static function stripSiteChromeFromPageHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (
            ! str_contains($html, 'data-voodbuilder-block')
            && ! str_contains($html, 'data-voodbuilder-gjs-site-header')
        ) {
            return self::stripChromeEditorBleedFromPageHtml($html);
        }

        $document = self::loadDocument($html);
        $xpath = new DOMXPath($document);
        $toRemove = [];

        foreach ($xpath->query('//*[@data-voodbuilder-block]') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $blockId = (string) $node->getAttribute('data-voodbuilder-block');

            if (self::isManagedBlockId($blockId)) {
                $toRemove[spl_object_id($node)] = $node;
            }
        }

        foreach ($xpath->query('//*[@data-voodbuilder-gjs-site-header]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $toRemove[spl_object_id($node)] = $node;
            }
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        return self::stripChromeEditorBleedFromPageHtml(self::serializeBodyChildren($document));
    }

    /** Legacy cleanup when chrome button labels were saved into page HTML. */
    public static function stripChromeEditorBleedFromPageHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'Button')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $toRemove = [];

        foreach ($document->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement || $node->getElementsByTagName('*')->length > 0) {
                continue;
            }

            if (self::isChromeEditorBleedText(trim($node->textContent))) {
                $toRemove[] = $node;
            }
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        return self::serializeBodyChildren($document);
    }

    protected static function isChromeEditorBleedText(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        $normalized = preg_replace('/\s+/', '', $text) ?? $text;

        return preg_match('/^(?:Button|Notifications)+$/', $normalized) === 1;
    }

    protected static function isManagedBlockId(string $blockId): bool
    {
        if ($blockId === 'site_header') {
            return true;
        }

        return SiteNavBlocks::isNavBlockId($blockId)
            || SiteFooterBlocks::isFooterBlockId($blockId);
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
