<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsHtmlSanitizer
{
    /**
     * GrapesJS map/image components call decodeURIComponent on query params.
     * Section templates may ship Google Maps embeds with `width=100%`, which throws URIError.
     */
    public static function sanitize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);

        $sanitized = preg_replace_callback(
            '/\bsrc=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $src = self::encodeMalformedPercentSequences($matches[2]);

                return 'src='.$quote.$src.$quote;
            },
            $decoded,
        );

        if (! is_string($sanitized)) {
            return $html;
        }

        return self::stripInvalidAttributes(self::stripLogoScrollRuntimeClones($sanitized));
    }

    /**
     * Runtime duplicates logo tracks in the canvas for a seamless marquee.
     * Strip those clones if they ever leak into saved HTML.
     */
    public static function stripLogoScrollRuntimeClones(string $html): string
    {
        if ($html === '' || (! str_contains($html, 'data-vb-logo-clone') && ! str_contains($html, 'vb-logo-scroll__track--clone'))) {
            return $html;
        }

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $wrapped = '<?xml encoding="UTF-8"><div id="voodbuilder-logo-scroll-root">'.$html.'</div>';
            $loaded = $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded !== true) {
            return $html;
        }

        $root = $document->getElementById('voodbuilder-logo-scroll-root');

        if (! $root instanceof \DOMElement) {
            return $html;
        }

        $xpath = new \DOMXPath($document);
        $clones = $xpath->query(
            './/*[@data-vb-logo-clone or contains(concat(" ", normalize-space(@class), " "), " vb-logo-scroll__track--clone ")]',
            $root,
        );

        if ($clones === false || $clones->length === 0) {
            return $html;
        }

        /** @var list<\DOMElement> $toRemove */
        $toRemove = [];

        foreach ($clones as $clone) {
            if ($clone instanceof \DOMElement) {
                $toRemove[] = $clone;
            }
        }

        foreach ($toRemove as $clone) {
            $clone->parentNode?->removeChild($clone);
        }

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output !== '' ? $output : $html;
    }

    /**
     * Recover CTA button text wiped by the GrapesJS editor (empty
     * <a data-voodbuilder-cta> with a surviving data-voodbuilder-cta-label).
     */
    public static function restoreEmptyCtaLabels(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-cta')) {
            return $html;
        }

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $wrapped = '<?xml encoding="UTF-8"><div id="voodbuilder-cta-root">'.$html.'</div>';
            $loaded = $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded !== true) {
            return $html;
        }

        $root = $document->getElementById('voodbuilder-cta-root');

        if (! $root instanceof \DOMElement) {
            return $html;
        }

        $changed = false;

        foreach ($root->getElementsByTagName('a') as $anchor) {
            if (! $anchor instanceof \DOMElement) {
                continue;
            }

            if ($anchor->getAttribute('data-voodbuilder-cta') !== 'true') {
                continue;
            }

            $text = trim(preg_replace('/\s+/u', ' ', $anchor->textContent) ?? '');

            if ($text !== '') {
                if (! $anchor->hasAttribute('data-voodbuilder-cta-label')) {
                    $anchor->setAttribute('data-voodbuilder-cta-label', $text);
                    $changed = true;
                }

                continue;
            }

            $label = trim($anchor->getAttribute('data-voodbuilder-cta-label'));

            if ($label === '') {
                $label = 'Button';
                $anchor->setAttribute('data-voodbuilder-cta-label', $label);
            }

            while ($anchor->firstChild !== null) {
                $anchor->removeChild($anchor->firstChild);
            }

            $anchor->appendChild($document->createTextNode($label));
            $changed = true;
        }

        if (! $changed) {
            return $html;
        }

        $inner = '';

        foreach ($root->childNodes as $child) {
            $inner .= $document->saveHTML($child);
        }

        return $inner !== '' ? $inner : $html;
    }

    /**
     * Remove uncompiled Blade fragments and other invalid attribute names from HTML.
     */
    public static function stripInvalidAttributes(string $html): string
    {
        if ($html === '' || (! str_contains($html, '@') && ! str_contains($html, '(@'))) {
            return $html;
        }

        $stripped = preg_replace(
            '/\s+(?:@\w+(?:\([^)]*\))?(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?|\([^)]*\)(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?)/',
            '',
            $html,
        );

        return is_string($stripped) ? $stripped : $html;
    }

    public static function encodeMalformedPercentSequences(string $value): string
    {
        return preg_replace('/%(?![0-9A-Fa-f]{2})/', '%25', $value) ?? $value;
    }

    public static function stripEditorOnlyElements(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        if (str_contains($html, 'data-voodbuilder-top-drop-spacer')) {
            $stripped = preg_replace(
                '/<div\b[^>]*\bdata-voodbuilder-top-drop-spacer\b[^>]*>\s*<\/div>/i',
                '',
                $html,
            );

            $html = is_string($stripped) ? $stripped : $html;
        }

        if (str_contains($html, 'data-voodbuilder-inner-drop')) {
            $stripped = preg_replace(
                '/<div\b[^>]*\bdata-voodbuilder-inner-drop\b[^>]*>\s*<\/div>/i',
                '',
                $html,
            );

            $html = is_string($stripped) ? $stripped : $html;
        }

        return self::stripEditorOnlyAttributes($html);
    }

    /**
     * Drop GrapesJS runtime attributes so published HTML matches the visual content
     * (same classes/structure) without editor-only data-gjs-* noise.
     *
     * GrapesJS may serialize JSON values with nested quotes
     * (e.g. data-gjs-resizable="{"ratioDefault":1}"), which breaks HTML attribute
     * parsing and leaves junk like ratioDefault / ratiodefault on the tag.
     */
    public static function stripEditorOnlyAttributes(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $needsStrip = str_contains($html, 'data-gjs-')
            || str_contains($html, 'ratioDefault')
            || str_contains($html, 'ratiodefault');

        if (! $needsStrip) {
            return $html;
        }

        // Remove JSON-valued data-gjs attrs before DOM parse (nested " breaks attributes).
        $preprocessed = preg_replace(
            '/\s+data-gjs-[a-zA-Z0-9_-]+="\{[^}]*\}"/',
            '',
            $html,
        );

        if (! is_string($preprocessed)) {
            $preprocessed = $html;
        }

        // Sweep residue left by a previous broken strip / HTML parser split.
        $preprocessed = preg_replace(
            '/\s*(?:ratioDefault|ratiodefault)(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?/i',
            '',
            $preprocessed,
        ) ?? $preprocessed;

        $preprocessed = preg_replace(
            '/\s*ratioDefault"\s*:\s*[^}"\s>]+\s*\}?"?/i',
            '',
            $preprocessed,
        ) ?? $preprocessed;

        if (! str_contains($preprocessed, 'data-gjs-')) {
            return $preprocessed;
        }

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $wrapped = '<?xml encoding="UTF-8"><div id="voodbuilder-sanitize-root">'.$preprocessed.'</div>';
            $loaded = $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded !== true) {
            return self::stripEditorOnlyAttributesWithRegexFallback($preprocessed);
        }

        $root = $document->getElementById('voodbuilder-sanitize-root');

        if (! $root instanceof \DOMElement) {
            return self::stripEditorOnlyAttributesWithRegexFallback($preprocessed);
        }

        $nodes = [$root, ...iterator_to_array($root->getElementsByTagName('*'))];

        foreach ($nodes as $node) {
            if (! $node instanceof \DOMElement || ! $node->hasAttributes()) {
                continue;
            }

            $toRemove = [];

            foreach ($node->attributes ?? [] as $attribute) {
                $name = $attribute->name;

                if (str_starts_with($name, 'data-gjs-') || strcasecmp($name, 'ratiodefault') === 0) {
                    $toRemove[] = $name;
                }
            }

            foreach ($toRemove as $name) {
                $node->removeAttribute($name);
            }
        }

        $inner = '';

        foreach ($root->childNodes as $child) {
            $inner .= $document->saveHTML($child);
        }

        return $inner !== '' ? $inner : $preprocessed;
    }

    /**
     * Last-resort strip when DOM parsing fails.
     */
    private static function stripEditorOnlyAttributesWithRegexFallback(string $html): string
    {
        $stripped = preg_replace(
            '/\s+data-gjs-[a-zA-Z0-9_-]+(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?/',
            '',
            $html,
        );

        if (! is_string($stripped)) {
            return $html;
        }

        $cleaned = preg_replace(
            '/\s*(?:ratioDefault|ratiodefault)(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?/i',
            '',
            $stripped,
        );

        return is_string($cleaned) ? $cleaned : $stripped;
    }
}
