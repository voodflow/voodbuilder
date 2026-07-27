<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * Marks Tailblocks-style CTAs as smart buttons without changing visual classes.
 */
final class GrapesJsSmartButtonAnnotator
{
    public static function annotate(string $html): string
    {
        $html = trim($html);

        if ($html === '' || (! str_contains($html, '<button') && ! str_contains($html, '<a'))) {
            return $html;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof \DOMElement) {
            return $html;
        }

        $xpath = new \DOMXPath($document);

        foreach ($xpath->query('.//button', $body) ?: [] as $button) {
            if (! $button instanceof \DOMElement) {
                continue;
            }

            if (self::shouldSkipButton($button)) {
                continue;
            }

            self::promote($button);
        }

        foreach ($xpath->query('.//a', $body) ?: [] as $anchor) {
            if (! $anchor instanceof \DOMElement) {
                continue;
            }

            if ($anchor->getAttribute('data-voodbuilder-cta') === 'true') {
                continue;
            }

            if (! self::looksLikeButton($anchor)) {
                continue;
            }

            self::promote($anchor);
        }

        $inner = '';

        foreach ($body->childNodes as $child) {
            $inner .= $document->saveHTML($child);
        }

        return trim($inner);
    }

    private static function shouldSkipButton(\DOMElement $button): bool
    {
        $type = strtolower($button->getAttribute('type'));

        if ($type === 'reset' || $type === 'submit') {
            return true;
        }

        if ($type === '' && self::hasAncestorTag($button, 'form')) {
            return true;
        }

        if ($button->hasAttribute('data-voodbuilder-bind')
            || $button->hasAttribute('data-cookie-preferences')
            || $button->hasAttribute('data-cc')
        ) {
            return true;
        }

        $class = ' '.$button->getAttribute('class').' ';

        return str_contains($class, ' cc-')
            || str_contains($class, ' carousel')
            || str_contains($class, ' swiper');
    }

    private static function hasAncestorTag(\DOMElement $element, string $tag): bool
    {
        $parent = $element->parentNode;

        while ($parent instanceof \DOMElement) {
            if (strcasecmp($parent->tagName, $tag) === 0) {
                return true;
            }

            $parent = $parent->parentNode;
        }

        return false;
    }

    private static function looksLikeButton(\DOMElement $anchor): bool
    {
        if ($anchor->getAttribute('role') === 'button') {
            return true;
        }

        $class = strtolower($anchor->getAttribute('class'));

        if ($class === '') {
            return false;
        }

        if (preg_match('/(?:^|\s)btn(?:-|\s|$)/', $class) === 1) {
            return true;
        }

        $hasHorizontalPad = str_contains($class, 'px-') || preg_match('/(?:^|\s)p-\d/', $class) === 1;
        $hasVerticalPad = str_contains($class, 'py-')
            || preg_match('/(?:^|\s)p-\d/', $class) === 1
            || preg_match('/(?:^|\s)h-(?:\d+|\[)/', $class) === 1
            || preg_match('/(?:^|\s)min-h-(?:\d+|\[)/', $class) === 1;
        $hasPad = $hasHorizontalPad && $hasVerticalPad;
        $hasRounded = str_contains($class, 'rounded');

        if (! $hasPad || ! $hasRounded) {
            return false;
        }

        // Filled CTAs
        $hasFill = str_contains($class, 'bg-indigo')
            || str_contains($class, 'bg-vp-brand')
            || str_contains($class, 'bg-blue')
            || str_contains($class, 'bg-gray-8')
            || str_contains($class, 'bg-black')
            || str_contains($class, 'bg-green')
            || str_contains($class, 'bg-teal')
            || str_contains($class, 'bg-emerald');

        // Outline / ghost CTAs (e.g. "Learn more" next to a primary button)
        $hasOutline = str_contains($class, 'border') && ! str_contains($class, 'border-0');
        $hasInlineFlex = str_contains($class, 'inline-flex');

        return $hasFill || $hasOutline || $hasInlineFlex;
    }

    private static function promote(\DOMElement $element): void
    {
        $label = trim(preg_replace('/\s+/', ' ', $element->textContent ?? '') ?? '');

        if ($label === '') {
            $label = 'Button';
        }

        $element->setAttribute('data-voodbuilder-cta', 'true');
        $element->setAttribute('data-voodbuilder-cta-label', $label);
        $element->setAttribute('role', 'button');

        if (! $element->hasAttribute('data-vb-link-type')) {
            $element->setAttribute('data-vb-link-type', 'url');
        }

        if (strtolower($element->tagName) === 'button') {
            // GrapesJS morphs button → anchor; keep as button in HTML source —
            // runtime scanner upgrades it. For paste/sections, convert to <a> now.
            $anchor = $element->ownerDocument?->createElement('a');

            if (! $anchor instanceof \DOMElement) {
                return;
            }

            foreach (iterator_to_array($element->attributes ?? []) as $attr) {
                if ($attr instanceof \DOMAttr) {
                    $anchor->setAttribute($attr->name, $attr->value);
                }
            }

            if (! $anchor->hasAttribute('href')) {
                $anchor->setAttribute('href', '#');
            }

            while ($element->firstChild) {
                $anchor->appendChild($element->firstChild);
            }

            $element->parentNode?->replaceChild($anchor, $element);
        } elseif (! $element->hasAttribute('href')) {
            $element->setAttribute('href', '#');
        }
    }
}
