<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Drop redundant inline background paints when published CSS already owns them
 * (`#id` rules / matching url()). Saved HTML should not keep `style="background-*"`.
 */
final class EditorBackgroundPublishNormalizer
{
    public static function preferCssBackgrounds(string $html, string $css): string
    {
        if ($html === '' || $css === '' || ! str_contains($html, 'style=')) {
            return $html;
        }

        if (! str_contains($html, 'background') && ! str_contains($html, 'url(')) {
            return $html;
        }

        $cssUrls = self::extractBackgroundUrls($css);

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

            if ($style === '' || ! str_contains(strtolower($style), 'background')) {
                continue;
            }

            $inlineUrls = self::extractBackgroundUrls($style);

            if (! self::elementCssOwnsPaint($node, $css, $cssUrls, $inlineUrls)) {
                continue;
            }

            $next = self::stripAuthorPaintFromStyle($style);

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

    /**
     * @param  array<string, true>  $cssUrls
     * @param  array<string, true>  $inlineUrls
     */
    private static function elementCssOwnsPaint(\DOMElement $node, string $css, array $cssUrls, array $inlineUrls): bool
    {
        $id = trim($node->getAttribute('id'));

        if ($id !== '' && preg_match('/#' . preg_quote($id, '/') . '\s*\{[^}]*background/i', $css) === 1) {
            return true;
        }

        foreach (array_keys($inlineUrls) as $url) {
            if (isset($cssUrls[$url])) {
                return true;
            }
        }

        return false;
    }

    private static function stripAuthorPaintFromStyle(string $style): string
    {
        $next = preg_replace(
            '/(?:^|;)\s*(?:background(?:-image|-size|-position|-repeat|-attachment|-color)?)\s*:[^;]*/i',
            '',
            $style,
        );

        if (! is_string($next)) {
            return $style;
        }

        $next = trim($next, " \t\n\r\0\x0B;");

        return $next === '' ? '' : $next . ';';
    }
}
