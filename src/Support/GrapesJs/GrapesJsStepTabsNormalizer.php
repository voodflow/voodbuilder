<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Upgrades step-nav blocks (STEP 1…N anchors + single content) to accessible tabs.
 */
final class GrapesJsStepTabsNormalizer
{
    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'STEP')) {
            return $html;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        if (! $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="vb-step-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        )) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return $html;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('vb-step-root');

        if (! $root instanceof DOMElement) {
            return $html;
        }

        $xpath = new DOMXPath($document);
        $changed = false;

        foreach ($xpath->query('.//div[.//a[contains(., "STEP")]]', $root) as $navContainer) {
            if (! $navContainer instanceof DOMElement) {
                continue;
            }

            if ($navContainer->getAttribute('role') === 'tablist'
                || $navContainer->getAttribute('class') !== '' && str_contains($navContainer->getAttribute('class'), 'vb-step-tabs__bar')) {
                continue;
            }

            $stepLinks = self::collectStepLinks($navContainer);

            if (count($stepLinks) < 2) {
                continue;
            }

            $contentParent = $navContainer->parentNode;

            if (! $contentParent instanceof DOMElement) {
                continue;
            }

            $contentNodes = self::collectContentSiblings($navContainer);

            if ($contentNodes === []) {
                continue;
            }

            $groupId = 'vb-step-'.substr(md5($navContainer->getNodePath()), 0, 8);
            $contentsWrapper = $document->createElement('div');
            $contentsWrapper->setAttribute('class', 'vb-step-tabs__contents');

            foreach ($stepLinks as $index => $link) {
                $panelId = $groupId.'-panel-'.$index;
                $link->setAttribute('role', 'tab');
                $link->setAttribute('aria-controls', $panelId);
                $link->setAttribute('aria-selected', $index === 0 ? 'true' : 'false');
                $link->setAttribute('tabindex', $index === 0 ? '0' : '-1');
                $link->setAttribute('href', '#'.$panelId);
                $link->removeAttribute('target');

                $class = trim(preg_replace(
                    '/\b(bg-gray-100|border-indigo-500|text-indigo-500)\b/',
                    '',
                    $link->getAttribute('class'),
                ) ?? '');

                $link->setAttribute('class', trim('vb-step-tabs__tab '.$class));

                if ($index === 0) {
                    $link->setAttribute('class', trim($link->getAttribute('class').' vb-step-tabs__tab--active'));
                }

                $panel = $document->createElement('div');
                $panel->setAttribute('role', 'tabpanel');
                $panel->setAttribute('id', $panelId);
                $panel->setAttribute('class', 'vb-step-tabs__panel');

                if ($index > 0) {
                    $panel->setAttribute('hidden', 'hidden');
                }

                if ($index === 0) {
                    foreach ($contentNodes as $node) {
                        $panel->appendChild($node->cloneNode(true));
                    }
                } else {
                    $panel->appendChild($contentNodes[0]->cloneNode(true));
                }

                $contentsWrapper->appendChild($panel);
            }

            $navContainer->setAttribute('role', 'tablist');
            $navContainer->setAttribute('class', trim('vb-step-tabs__bar '.$navContainer->getAttribute('class')));

            foreach ($contentNodes as $node) {
                $node->parentNode?->removeChild($node);
            }

            if ($contentParent->nextSibling) {
                $contentParent->insertBefore($contentsWrapper, $contentParent->nextSibling);
            } else {
                $contentParent->appendChild($contentsWrapper);
            }

            $wrapper = $contentParent;

            while ($wrapper->parentNode instanceof DOMElement && $wrapper->parentNode->getAttribute('id') !== 'vb-step-root') {
                if ($wrapper->parentNode->tagName === 'section') {
                    $wrapper = $wrapper->parentNode;

                    break;
                }

                $wrapper = $wrapper->parentNode;
            }

            $wrapper->setAttribute('data-voodbuilder-step-tabs', '');
            $wrapper->setAttribute('data-vb-tab-active-class', 'vb-step-tabs__tab--active');

            $changed = true;
        }

        if (! $changed) {
            return $html;
        }

        $normalized = '';

        foreach ($root->childNodes as $child) {
            $normalized .= $document->saveHTML($child);
        }

        return $normalized;
    }

    /**
     * @return list<DOMElement>
     */
    protected static function collectStepLinks(DOMElement $navContainer): array
    {
        $links = [];

        foreach ($navContainer->childNodes as $child) {
            if (! $child instanceof DOMElement || $child->tagName !== 'a') {
                continue;
            }

            if (! preg_match('/\bSTEP\s*\d+\b/i', $child->textContent ?? '')) {
                continue;
            }

            $links[] = $child;
        }

        return $links;
    }

    /**
     * @return list<DOMElement>
     */
    protected static function collectContentSiblings(DOMElement $navContainer): array
    {
        $nodes = [];
        $sibling = $navContainer->nextSibling;

        while ($sibling !== null) {
            if ($sibling instanceof DOMElement) {
                $nodes[] = $sibling;
            }

            $sibling = $sibling->nextSibling;
        }

        return $nodes;
    }
}
