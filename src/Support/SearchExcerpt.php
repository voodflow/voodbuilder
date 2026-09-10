<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Str;

/**
 * Plain-text snippets for site search results (no raw Markdown / HTML noise).
 */
final class SearchExcerpt
{
    public static function fromMarkdown(?string $markdown, int $limit = 160, ?string $term = null): ?string
    {
        $plain = self::plainFromMarkdown($markdown);

        if ($plain === null) {
            return null;
        }

        return self::aroundMatch($plain, $term ?? '', $limit);
    }

    public static function fromHtml(?string $html, int $limit = 160, ?string $term = null): ?string
    {
        $plain = self::plainFromHtml($html);

        if ($plain === null) {
            return null;
        }

        return self::aroundMatch($plain, $term ?? '', $limit);
    }

    /**
     * Prefer short dedicated fields, then body — always centered on the query when possible.
     *
     * @param  list<string|null>  $preferred
     * @return array{excerpt: string|null, excerpt_html: string|null}
     */
    public static function present(string $term, array $preferred, ?string $body, int $limit = 160): array
    {
        $term = trim($term);
        $limit = max(40, $limit);

        foreach ($preferred as $candidate) {
            $plain = self::normalizeWhitespace((string) $candidate);
            if ($plain === '') {
                continue;
            }
            if ($term !== '' && mb_stripos($plain, $term) !== false) {
                $snippet = self::aroundMatch($plain, $term, $limit);

                return [
                    'excerpt' => $snippet,
                    'excerpt_html' => self::highlight((string) $snippet, $term),
                ];
            }
        }

        $bodyPlain = self::normalizeWhitespace((string) ($body ?? ''));
        if ($bodyPlain !== '') {
            $snippet = self::aroundMatch($bodyPlain, $term, $limit);

            return [
                'excerpt' => $snippet,
                'excerpt_html' => $snippet !== null ? self::highlight($snippet, $term) : null,
            ];
        }

        foreach ($preferred as $candidate) {
            $plain = self::normalizeWhitespace((string) $candidate);
            if ($plain === '') {
                continue;
            }
            $snippet = self::aroundMatch($plain, $term, $limit);

            return [
                'excerpt' => $snippet,
                'excerpt_html' => $snippet !== null ? self::highlight($snippet, $term) : null,
            ];
        }

        return ['excerpt' => null, 'excerpt_html' => null];
    }

    public static function plainFromMarkdown(?string $markdown): ?string
    {
        $text = trim((string) $markdown);

        if ($text === '') {
            return null;
        }

        $text = preg_replace('/```[\s\S]*?```/', ' ', $text) ?? $text;
        $text = preg_replace('/~~~[\s\S]*?~~~/', ' ', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;
        $text = preg_replace('/^:::\s*\w*.*$/m', '', $text) ?? $text;
        $text = preg_replace('/^:::$/m', '', $text) ?? $text;
        $text = preg_replace('/^#{1,6}\s+/m', '', $text) ?? $text;
        $text = preg_replace('/^>\s?/m', '', $text) ?? $text;
        $text = preg_replace('/^\s*[-*+]\s+/m', '', $text) ?? $text;
        $text = preg_replace('/^\s*\d+\.\s+/m', '', $text) ?? $text;
        $text = preg_replace('/(\*\*|__)(.*?)\1/s', '$2', $text) ?? $text;
        $text = preg_replace('/(\*|_)(.*?)\1/s', '$2', $text) ?? $text;
        $text = preg_replace('/~~(.*?)~~/s', '$1', $text) ?? $text;
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/<https?:\/\/[^>]+>/', '', $text) ?? $text;
        $text = preg_replace('/^\|.*\|$/m', ' ', $text) ?? $text;
        $text = preg_replace('/\|/', ' ', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $plain = self::normalizeWhitespace($text);

        return $plain !== '' ? $plain : null;
    }

    public static function plainFromHtml(?string $html): ?string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = self::normalizeWhitespace($text);

        return $plain !== '' ? $plain : null;
    }

    public static function aroundMatch(string $plain, string $term, int $limit = 160): ?string
    {
        $plain = self::normalizeWhitespace($plain);
        if ($plain === '') {
            return null;
        }

        $limit = max(40, $limit);
        $term = trim($term);

        if ($term === '' || mb_stripos($plain, $term) === false) {
            return Str::limit($plain, $limit, '…');
        }

        $pos = (int) mb_stripos($plain, $term);
        $termLen = mb_strlen($term);
        $radius = max(24, (int) floor(($limit - $termLen) / 2));
        $start = max(0, $pos - $radius);
        $end = min(mb_strlen($plain), $pos + $termLen + $radius);
        $snippet = mb_substr($plain, $start, $end - $start);

        if ($start > 0) {
            $snippet = '…'.$snippet;
        }
        if ($end < mb_strlen($plain)) {
            $snippet .= '…';
        }

        return Str::limit($snippet, $limit + 2, '…');
    }

    public static function highlight(string $plain, string $term): string
    {
        $term = trim($term);

        if ($term === '' || $plain === '') {
            return e($plain);
        }

        $parts = preg_split('/('.preg_quote($term, '/').')/iu', $plain, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return e($plain);
        }

        $html = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (strcasecmp($part, $term) === 0) {
                $html .= '<mark class="vb-search-mark">'.e($part).'</mark>';
            } else {
                $html .= e($part);
            }
        }

        return $html;
    }

    public static function score(string $term, string $title, ?string $meta = null, ?string $body = null): int
    {
        $term = mb_strtolower(trim($term));

        if ($term === '') {
            return 0;
        }

        $score = 0;
        $titleL = mb_strtolower($title);

        if ($titleL === $term) {
            $score += 100;
        } elseif (str_starts_with($titleL, $term)) {
            $score += 80;
        } elseif (str_contains($titleL, $term)) {
            $score += 50;
        }

        if (filled($meta) && str_contains(mb_strtolower((string) $meta), $term)) {
            $score += 25;
        }

        if (filled($body)) {
            $bodyL = mb_strtolower((string) $body);
            if (str_contains($bodyL, $term)) {
                $score += 10;
                $pos = mb_stripos($bodyL, $term);
                if ($pos !== false && $pos < 80) {
                    $score += 5;
                }
            }
        }

        return $score;
    }

    protected static function normalizeWhitespace(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
