<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Drop redundant inline background-image url() when the same asset is already
 * painted by the published page CSS (Style Manager / #id rules).
 *
 * Grapes persists both inline style and CssComposer rules; Chromium then fetches
 * the same photo twice (inline + stylesheet), which shows up as 2–4× Network hits
 * for a single -lg conversion when Disable cache is on.
 */
final class EditorBackgroundPublishNormalizer
{
    public static function preferCssBackgrounds(string $html, string $css): string
    {
        if ($html === '' || $css === '' || ! str_contains($html, 'url(')) {
            return $html;
        }

        $cssUrls = self::extractBackgroundUrls($css);

        if ($cssUrls === []) {
            return $html;
        }

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $wrapped = '<?xml encoding="UTF-8"><div id="voodbuilder-bg-dedupe-root">' . $html . '</div>';
            $loaded = $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded !== true) {
            return $html;
        }

        $root = $document->getElementById('voodbuilder-bg-dedupe-root');

        if (! $root instanceof \DOMElement) {
            return $html;
        }

        $changed = false;
        $nodes = [$root, ...iterator_to_array($root->getElementsByTagName('*'))];

        foreach ($nodes as $node) {
            if (! $node instanceof \DOMElement || ! $node->hasAttribute('style')) {
                continue;
            }

            $style = (string) $node->getAttribute('style');

            if ($style === '' || ! str_contains($style, 'url(')) {
                continue;
            }

            $inlineUrls = self::extractBackgroundUrls($style);

            if ($inlineUrls === []) {
                continue;
            }

            $redundant = false;

            foreach (array_keys($inlineUrls) as $url) {
                if (isset($cssUrls[$url])) {
                    $redundant = true;

                    break;
                }
            }

            if (! $redundant) {
                continue;
            }

            $next = self::stripBackgroundImageUrlsFromStyle($style);

            if ($next === $style) {
                continue;
            }

            if ($next === '') {
                $node->removeAttribute('style');
            } else {
                $node->setAttribute('style', $next);
            }

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
     * @return array<string, true>
     */
    private static function extractBackgroundUrls(string $cssOrStyle): array
    {
        if (! preg_match_all('/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', $cssOrStyle, $matches)) {
            return [];
        }

        $out = [];

        foreach ($matches[2] as $raw) {
            $url = trim((string) $raw);

            if ($url === '' || str_starts_with($url, 'data:')) {
                continue;
            }

            $out[self::normalizeUrlKey($url)] = true;
        }

        return $out;
    }

    private static function normalizeUrlKey(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            return $path;
        }

        return $url;
    }

    private static function stripBackgroundImageUrlsFromStyle(string $style): string
    {
        // Drop background-image declarations that contain url(...). Keep solid colors / sizes.
        $next = preg_replace(
            '/(?:^|;)\s*background-image\s*:\s*[^;]*url\([^)]*\)[^;]*(?=;|$)/i',
            '',
            $style,
        );

        if (! is_string($next)) {
            return $style;
        }

        // Shorthand `background: ... url(...)` — remove only when a url is present.
        $next = preg_replace(
            '/(?:^|;)\s*background\s*:\s*[^;]*url\([^)]*\)[^;]*(?=;|$)/i',
            '',
            $next,
        );

        if (! is_string($next)) {
            return $style;
        }

        $next = trim($next, " \t\n\r\0\x0B;");

        return $next === '' ? '' : $next . ';';
    }
}
