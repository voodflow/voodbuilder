<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;

/**
 * Editor Html Sanitizer.
 */
final class EditorHtmlSanitizer
{
    /**
     * Editor map/image components call decodeURIComponent on query params.
     * Section templates may ship Google Maps embeds with `width=100%`, which throws URIError.
     */
    public static function sanitize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        // Heal already-persisted broken JSON attrs (e.g. visibility) before other passes.
        $html = self::repairBrokenJsonDataAttributes($html);

        // Never html_entity_decode() the whole document: it turns &quot; inside
        // attribute values back into raw ", which breaks JSON data-* attrs
        // (vforms visibility) and leaves GrapesJS unable to serialize/reload the page.
        // Decode entities only inside src= values where maps embeds need % / & fixes.
        $sanitized = preg_replace_callback(
            '/\bsrc=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $src = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5);
                $src = self::encodeMalformedPercentSequences($src);

                return 'src='.$quote.$src.$quote;
            },
            $html,
        );

        if (! is_string($sanitized)) {
            return $html;
        }

        return FontStylesheets::sanitizeInlineFontFamilies(
            self::repairAnimatedBlocks(
                self::normalizeSameOriginUrls(
                    self::stripInvalidAttributes(self::stripLogoScrollRuntimeClones($sanitized)),
                ),
            ),
        );
    }

    /**
     * Rewrite absolute app-origin URLs to root-relative.
     *
     * The link picker and asset helpers serialize absolute URLs into the canvas. Baking the
     * origin into stored HTML breaks every internal link and image as soon as the site moves
     * to another domain or port (dev → staging → prod, tenant domains).
     */
    public static function normalizeSameOriginUrls(string $html): string
    {
        if ($html === '' || ! str_contains($html, '//')) {
            return $html;
        }

        foreach (self::appOrigins() as $origin) {
            $quoted = preg_quote($origin, '~');

            // "https://host/pages/x" → "/pages/x"
            $html = preg_replace('~'.$quoted.'(?=/)~i', '', $html) ?? $html;
            // "https://host" (bare, or followed by query/fragment) → "/"
            $html = preg_replace('~'.$quoted.'(?=["\'?#\s>])~i', '/', $html) ?? $html;
        }

        return $html;
    }

    /**
     * Origins that belong to this installation. Both are needed: `app.url` is the configured
     * canonical host, `url('/')` is what the running request (or proxy) actually serves.
     *
     * @return list<string>
     */
    private static function appOrigins(): array
    {
        $origins = [];

        foreach ([config('app.url'), url('/')] as $candidate) {
            $origin = self::originFromUrl((string) $candidate);

            if ($origin !== '' && ! in_array($origin, $origins, true)) {
                $origins[] = $origin;
            }
        }

        return $origins;
    }

    private static function originFromUrl(string $url): string
    {
        $parts = parse_url(trim($url));
        $host = $parts['host'] ?? '';

        if (! is_string($host) || $host === '') {
            return '';
        }

        $scheme = is_string($parts['scheme'] ?? null) ? $parts['scheme'] : 'http';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$host.$port;
    }

    /**
     * Re-escape JSON data attributes that were persisted with raw quotes inside
     * double-quoted HTML attributes (invalid HTML that blanks the editor canvas).
     */
    public static function repairBrokenJsonDataAttributes(string $html): string
    {
        foreach (['data-vforms-visibility'] as $attribute) {
            $html = self::reescapeRawJsonAttribute($html, $attribute);
        }

        return $html;
    }

    private static function reescapeRawJsonAttribute(string $html, string $attribute): string
    {
        // Only raw quotes after the opening brace are invalid HTML
        // (data-vforms-visibility="{"logic":...}). Properly escaped values use
        // {&quot;...} and must not be re-encoded into &amp;quot;.
        if (! str_contains($html, $attribute.'="{"')) {
            return $html;
        }

        $needle = $attribute.'="';
        $offset = 0;
        $length = strlen($html);
        $output = '';

        while (($pos = strpos($html, $needle, $offset)) !== false) {
            $output .= substr($html, $offset, $pos - $offset);
            $valueStart = $pos + strlen($needle);
            $isBrokenJson = ($html[$valueStart] ?? '') === '{'
                && ($html[$valueStart + 1] ?? '') === '"';

            if (! $isBrokenJson) {
                $end = strpos($html, '"', $valueStart);

                if ($end === false) {
                    $output .= substr($html, $pos);

                    return $output;
                }

                $output .= substr($html, $pos, $end - $pos + 1);
                $offset = $end + 1;

                continue;
            }

            $depth = 0;
            $cursor = $valueStart;

            for (; $cursor < $length; $cursor++) {
                $char = $html[$cursor];

                if ($char === '{') {
                    $depth++;
                } elseif ($char === '}') {
                    $depth--;

                    if ($depth === 0) {
                        $cursor++;
                        break;
                    }
                }
            }

            $json = substr($html, $valueStart, $cursor - $valueStart);

            if (($html[$cursor] ?? '') === '"') {
                $cursor++;
            }

            $output .= $attribute.'="'.htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8').'"';
            $offset = $cursor;
        }

        return $output.substr($html, $offset);
    }

    /**
     * Heal Editor withProps corruption: empty animated markers and counters
     * serialized as bare `<span object="">2.7K</span>` without data-vb-count-*.
     */
    public static function repairAnimatedBlocks(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $needsRepair = str_contains($html, 'object=')
            || str_contains($html, ' object>')
            || str_contains($html, 'data-voodbuilder-animated-')
            || str_contains($html, 'data-voodbuilder-logo-scroll');

        if (! $needsRepair) {
            return $html;
        }

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $wrapped = '<?xml encoding="UTF-8"><div id="voodbuilder-animated-root">'.$html.'</div>';
            $loaded = $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded !== true) {
            return $html;
        }

        $root = $document->getElementById('voodbuilder-animated-root');

        if (! $root instanceof \DOMElement) {
            return $html;
        }

        $changed = false;

        foreach (self::collectDomElements($root) as $element) {
            if (! $element instanceof \DOMElement) {
                continue;
            }

            foreach ([
                'data-voodbuilder-animated-cta',
                'data-voodbuilder-animated-stats',
                'data-voodbuilder-animated-counter',
                'data-voodbuilder-logo-scroll',
                'data-vb-items-root',
                'data-vb-item',
            ] as $marker) {
                if ($element->hasAttribute($marker) && $element->getAttribute($marker) === '') {
                    $element->setAttribute($marker, '1');
                    $changed = true;
                }
            }

            $hadObject = $element->hasAttribute('object');

            if ($hadObject) {
                $element->removeAttribute('object');
                $changed = true;
            }

            $isCounterCandidate = $element->hasAttribute('data-voodbuilder-animated-counter')
                || $element->hasAttribute('data-vb-count-to')
                || str_contains($element->getAttribute('class'), 'vb-animated-counter')
                || self::isStatsItemCounterSpan($element)
                || ($hadObject && self::isOrphanCounterSpan($element));

            if (! $isCounterCandidate) {
                continue;
            }

            if (self::repairCounterElement($element)) {
                $changed = true;
            }
        }

        foreach (self::collectDomElements($root) as $element) {
            if (! $element instanceof \DOMElement) {
                continue;
            }

            if (! $element->hasAttribute('data-voodbuilder-animated-stats')) {
                continue;
            }

            if (self::repairAnimatedStatsLayout($element)) {
                $changed = true;
            }
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
     * @return list<\DOMElement>
     */
    private static function collectDomElements(\DOMElement $root): array
    {
        $elements = [];

        foreach ($root->getElementsByTagName('*') as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    private static function isStatsItemCounterSpan(\DOMElement $element): bool
    {
        if (strtolower($element->tagName) !== 'span') {
            return false;
        }

        $parent = $element->parentNode;

        if (! $parent instanceof \DOMElement || ! $parent->hasAttribute('data-vb-item')) {
            return false;
        }

        $section = $parent->parentNode;

        while ($section instanceof \DOMNode) {
            if ($section instanceof \DOMElement && $section->hasAttribute('data-voodbuilder-animated-stats')) {
                return true;
            }

            $section = $section->parentNode;
        }

        return false;
    }

    private static function isOrphanCounterSpan(\DOMElement $element): bool
    {
        if (strtolower($element->tagName) !== 'span') {
            return false;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $element->textContent) ?? '');

        return self::parseCounterLabel($text) !== null;
    }

    private static function repairCounterElement(\DOMElement $element): bool
    {
        $changed = false;

        if (! $element->hasAttribute('data-voodbuilder-animated-counter')
            || $element->getAttribute('data-voodbuilder-animated-counter') === '') {
            $element->setAttribute('data-voodbuilder-animated-counter', '1');
            $changed = true;
        }

        $class = trim($element->getAttribute('class'));
        $classes = $class === '' ? [] : (preg_split('/\s+/', $class) ?: []);

        if (! in_array('vb-animated-counter', $classes, true)) {
            $classes[] = 'vb-animated-counter';
            $changed = true;
        }

        if (! in_array('tabular-nums', $classes, true)) {
            $classes[] = 'tabular-nums';
            $changed = true;
        }

        if ($changed) {
            $element->setAttribute('class', implode(' ', array_values(array_unique($classes))));
        }

        if ($element->getAttribute('data-vb-count-to') !== '') {
            $trigger = self::normalizeCounterTrigger($element->getAttribute('data-vb-count-trigger'));

            if ($element->getAttribute('data-vb-count-trigger') !== $trigger) {
                $element->setAttribute('data-vb-count-trigger', $trigger);
                $changed = true;
            }

            if (! $element->hasAttribute('data-vb-count-label')) {
                $element->setAttribute(
                    'data-vb-count-label',
                    trim(preg_replace('/\s+/u', ' ', $element->textContent) ?? ''),
                );
                $changed = true;
            }

            return $changed;
        }

        $parsed = self::parseCounterLabel(trim(preg_replace('/\s+/u', ' ', $element->textContent) ?? ''));

        if ($parsed === null) {
            return $changed;
        }

        self::applyParsedCounterAttributes($element, $parsed, $element->getAttribute('data-vb-count-source') ?: 'static');

        return true;
    }

    /**
     * Update animated-counter data-* attrs from a bound/dynamic display value.
     */
    public static function syncCounterElementFromValue(\DOMElement $element, string $value): bool
    {
        $parsed = self::parseCounterLabel(trim(preg_replace('/\s+/u', ' ', $value) ?? ''));

        if ($parsed === null) {
            $label = trim($value);

            if ($label === '') {
                return false;
            }

            $element->setAttribute('data-voodbuilder-animated-counter', '1');
            $element->setAttribute('data-vb-count-source', 'dynamic');
            $element->setAttribute('data-vb-count-label', $label);
            self::replaceElementText($element, self::counterInitialDisplayText($element, $label));

            return true;
        }

        self::applyParsedCounterAttributes($element, $parsed, 'dynamic');
        self::replaceElementText(
            $element,
            self::counterInitialDisplayText(
                $element,
                self::formatCounterLabel($parsed['to'], $parsed['decimals'], $parsed['prefix'], $parsed['suffix']),
            ),
        );

        return true;
    }

    /**
     * Deferred triggers keep the "from" value in the HTML until JS animates.
     */
    private static function counterInitialDisplayText(\DOMElement $element, string $finalLabel): string
    {
        $trigger = self::normalizeCounterTrigger($element->getAttribute('data-vb-count-trigger'));

        if ($trigger === 'always') {
            return $finalLabel;
        }

        $from = filter_var($element->getAttribute('data-vb-count-from') ?: '0', FILTER_VALIDATE_FLOAT);
        $decimals = max(0, min(2, (int) ($element->getAttribute('data-vb-count-decimals') ?: '0')));
        $prefix = $element->getAttribute('data-vb-count-prefix');
        $suffix = $element->getAttribute('data-vb-count-suffix');

        return self::formatCounterLabel(
            $from === false ? 0.0 : $from,
            $decimals,
            $prefix,
            $suffix,
        );
    }

    private static function replaceElementText(\DOMElement $element, string $text): void
    {
        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }

        $element->appendChild($element->ownerDocument->createTextNode($text));
    }

    /**
     * @param  array{to: float, decimals: int, prefix: string, suffix: string}  $parsed
     */
    private static function applyParsedCounterAttributes(\DOMElement $element, array $parsed, string $source): void
    {
        $element->setAttribute('data-voodbuilder-animated-counter', '1');
        $element->setAttribute('data-vb-count-from', $element->getAttribute('data-vb-count-from') ?: '0');
        $element->setAttribute('data-vb-count-to', (string) $parsed['to']);
        $element->setAttribute('data-vb-count-decimals', (string) $parsed['decimals']);
        $element->setAttribute('data-vb-count-prefix', $parsed['prefix']);
        $element->setAttribute('data-vb-count-suffix', $parsed['suffix']);
        $element->setAttribute('data-vb-count-duration', $element->getAttribute('data-vb-count-duration') ?: '1600');
        $element->setAttribute('data-vb-count-delay', $element->getAttribute('data-vb-count-delay') ?: '0');
        $element->setAttribute('data-vb-count-trigger', self::normalizeCounterTrigger($element->getAttribute('data-vb-count-trigger')));
        $element->setAttribute('data-vb-count-easing', $element->getAttribute('data-vb-count-easing') ?: 'ease-out');
        $element->setAttribute('data-vb-count-source', $source !== '' ? $source : 'static');
        $element->setAttribute(
            'data-vb-count-label',
            self::formatCounterLabel($parsed['to'], $parsed['decimals'], $parsed['prefix'], $parsed['suffix']),
        );
    }

    /**
     * @return 'always'|'visible'|'hover'|'click'
     */
    private static function normalizeCounterTrigger(?string $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return match ($value) {
            'always', 'immediate' => 'always',
            'hover' => 'hover',
            'click', 'active' => 'click',
            default => 'visible',
        };
    }

    /**
     * @return array{to: float, decimals: int, prefix: string, suffix: string}|null
     */
    private static function parseCounterLabel(string $label): ?array
    {
        $text = trim($label);

        if ($text === '') {
            return null;
        }

        if (! preg_match(
            '/^([^\d\-−]*)([-−]?\d{1,3}(?:[.,]\d{3})*(?:[.,]\d+)?|\d+(?:[.,]\d+)?)(.*)$/u',
            $text,
            $match,
        )) {
            return null;
        }

        $prefix = $match[1] ?? '';
        $rawNumber = str_replace('−', '-', (string) ($match[2] ?? ''));
        $suffix = $match[3] ?? '';
        $normalized = str_contains($rawNumber, ',') && str_contains($rawNumber, '.')
            ? str_replace(',', '', $rawNumber)
            : str_replace(',', '.', (string) preg_replace('/,(?=\d{3}\b)/', '', $rawNumber));
        $to = filter_var($normalized, FILTER_VALIDATE_FLOAT);

        if ($to === false) {
            return null;
        }

        $fraction = str_contains($normalized, '.')
            ? strlen(explode('.', $normalized, 2)[1] ?? '')
            : 0;

        return [
            'to' => $to,
            'decimals' => max(0, min(2, $fraction)),
            'prefix' => $prefix,
            'suffix' => $suffix,
        ];
    }

    private static function formatCounterLabel(float $to, int $decimals, string $prefix, string $suffix): string
    {
        return $prefix.number_format($to, $decimals, '.', ',').$suffix;
    }

    private static function repairAnimatedStatsLayout(\DOMElement $section): bool
    {
        $changed = false;
        $itemCount = max(1, (int) ($section->getAttribute('data-vb-item-count') ?: '0'));
        $columnsAttr = (int) ($section->getAttribute('data-vb-item-columns') ?: '0');
        $columns = $columnsAttr > 0
            ? max(1, min(6, $columnsAttr))
            : max(1, min(6, $itemCount > 0 ? $itemCount : 4));

        if (! $section->hasAttribute('data-vb-item-columns') || $section->getAttribute('data-vb-item-columns') === '') {
            $section->setAttribute('data-vb-item-columns', (string) $columns);
            $changed = true;
        }

        $root = null;

        foreach (self::collectDomElements($section) as $element) {
            if ($element->hasAttribute('data-vb-items-root')) {
                $root = $element;
                break;
            }
        }

        if (! $root instanceof \DOMElement) {
            return $changed;
        }

        $gridClasses = self::gridClassesForColumns($columns);
        $class = trim($root->getAttribute('class'));
        $classes = $class === '' ? [] : (preg_split('/\s+/', $class) ?: []);
        $kept = array_values(array_filter(
            $classes,
            static fn (string $name): bool => ! preg_match(
                '/^(?:flex|flex-wrap|-m-4|gap-\d+|grid|grid-cols-\d+|sm:grid-cols-\d+|md:grid-cols-\d+|lg:grid-cols-\d+|text-center)$/',
                $name,
            ),
        ));
        $next = array_values(array_unique([...$kept, ...$gridClasses, 'text-center']));

        if ($next !== $classes) {
            $root->setAttribute('class', implode(' ', $next));
            $changed = true;
        }

        if ($root->getAttribute('data-vb-item-columns') !== (string) $columns) {
            $root->setAttribute('data-vb-item-columns', (string) $columns);
            $changed = true;
        }

        $root->setAttribute('data-vb-items-root', $root->getAttribute('data-vb-items-root') ?: '1');

        $style = trim($root->getAttribute('style'));
        $withoutColumns = trim((string) preg_replace('/(?:^|;)\s*--vb-item-columns\s*:\s*[^;]*/i', '', $style), '; ');
        $withoutTemplate = trim((string) preg_replace('/(?:^|;)\s*grid-template-columns\s*:\s*[^;]*/i', '', $withoutColumns), '; ');
        $nextStyle = ($withoutTemplate !== '' ? $withoutTemplate.'; ' : '').'--vb-item-columns: '.$columns;

        if ($root->getAttribute('style') !== $nextStyle) {
            $root->setAttribute('style', $nextStyle);
            $changed = true;
        }

        foreach (self::collectDomElements($root) as $item) {
            if (! $item->hasAttribute('data-vb-item')) {
                continue;
            }

            $itemClass = trim($item->getAttribute('class'));
            $itemClasses = $itemClass === '' ? [] : (preg_split('/\s+/', $itemClass) ?: []);
            $itemKept = array_values(array_filter(
                $itemClasses,
                static fn (string $name): bool => ! preg_match(
                    '/^(?:sm|md|lg|xl):w-1\/\d+$|^w-1\/\d+$|^w-full$/',
                    $name,
                ),
            ));

            if (! in_array('p-4', $itemKept, true)) {
                $itemKept[] = 'p-4';
            }

            if (! in_array('text-center', $itemKept, true)) {
                $itemKept[] = 'text-center';
            }

            if ($itemKept !== $itemClasses) {
                $item->setAttribute('class', implode(' ', $itemKept));
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @return list<string>
     */
    private static function gridClassesForColumns(int $columns): array
    {
        return ['grid', 'gap-4'];
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
     * Recover CTA button text wiped by the visual editor (empty
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

    /**
     * Drop page HTML that is only bare text (no block markup). Often left after deleting
     * a section when Grapes serializes orphan textnodes — visible on canvas, not in Layers.
     */
    public static function stripOrphanPageContentText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $html = self::stripEditorOnlyElements($html);

        if (trim($html) === '') {
            return '';
        }

        if (! str_contains($html, '<')) {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8"?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded !== true) {
            return $html;
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof \DOMElement) {
            return $html;
        }

        $removedText = false;

        foreach ([...$body->childNodes] as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $body->removeChild($child);
                $removedText = true;
            }
        }

        if (! $removedText) {
            return $html;
        }

        $clean = '';

        foreach ($body->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
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

        if (str_contains($html, 'data-voodbuilder-bottom-drop-spacer')) {
            $stripped = preg_replace(
                '/<div\b[^>]*\bdata-voodbuilder-bottom-drop-spacer\b[^>]*>\s*<\/div>/i',
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
     * Drop Editor runtime attributes so published HTML matches the visual content
     * (same classes/structure) without editor-only data-gjs-* noise.
     *
     * Editor may serialize JSON values with nested quotes
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
