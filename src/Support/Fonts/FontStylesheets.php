<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Fonts;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Resolve public stylesheet URLs for fonts used on a published page.
 *
 * Fontsource CSS is emitted by Vite into public/build; a lightweight manifest
 * (optional) maps font id → built CSS href. Until the manifest exists, Inter /
 * JetBrains still come from theme.css.
 */
final class FontStylesheets
{
    /**
     * @param  list<string>|null  $fontIds
     * @return list<string>
     */
    public static function urlsFor(?array $fontIds): array
    {
        if ($fontIds === null || $fontIds === []) {
            return [];
        }

        $manifest = self::manifest();
        $urls = [];

        foreach ($fontIds as $id) {
            $key = trim((string) $id);

            if ($key === '' || ! isset($manifest[$key])) {
                continue;
            }

            $entry = $manifest[$key];

            if (is_array($entry)) {
                foreach ($entry as $href) {
                    $href = trim((string) $href);

                    if ($href !== '') {
                        $urls[] = $href;
                    }
                }

                continue;
            }

            $href = trim((string) $entry);

            if ($href !== '') {
                $urls[] = $href;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * WOFF2 file URLs referenced by published font stylesheets (for &lt;link rel="preload"&gt;).
     *
     * Preloading cuts the FOUT from font-display:swap/block on page refresh.
     *
     * @param  list<string>|null  $fontIds
     * @return list<string>
     */
    public static function fontFileUrlsFor(?array $fontIds): array
    {
        $files = [];

        foreach (self::urlsFor($fontIds) as $cssUrl) {
            foreach (self::woff2UrlsFromStylesheet($cssUrl) as $fileUrl) {
                $files[] = $fileUrl;
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    public static function woff2UrlsFromStylesheet(string $cssUrl): array
    {
        $path = self::publicPathFromUrl($cssUrl);

        if ($path === null) {
            return [];
        }

        $css = @file_get_contents($path);

        if (! is_string($css) || $css === '') {
            return [];
        }

        if (preg_match_all('/url\(\s*(["\']?)([^)\'"\s]+\.woff2)\1\s*\)/i', $css, $matches) < 1) {
            return [];
        }

        $urls = [];

        foreach ($matches[2] as $raw) {
            $href = self::normalizeAssetUrl((string) $raw, $cssUrl);

            if ($href !== '') {
                $urls[] = $href;
            }
        }

        return array_values(array_unique($urls));
    }

    private static function publicPathFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            // Relative /build/... without scheme.
            $path = str_starts_with($url, '/') ? $url : '/'.ltrim($url, '/');
        }

        $full = public_path(ltrim($path, '/'));

        return is_file($full) ? $full : null;
    }

    private static function normalizeAssetUrl(string $raw, string $cssUrl): string
    {
        $href = trim($raw);

        if ($href === '' || str_starts_with($href, 'data:')) {
            return '';
        }

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://') || str_starts_with($href, '//')) {
            return $href;
        }

        if (str_starts_with($href, '/')) {
            return $href;
        }

        // Resolve relative to the CSS file directory (rare; Vite emits root-absolute).
        $cssPath = parse_url($cssUrl, PHP_URL_PATH);
        $base = is_string($cssPath) ? dirname($cssPath) : '/build/assets';

        return rtrim(str_replace('\\', '/', $base), '/').'/'.ltrim($href, '/');
    }

    /**
     * @return array<string, string>
     */
    public static function manifest(): array
    {
        $path = public_path('build/voodbuilder-fonts-manifest.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Persist detected font ids onto a builder payload array.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function withDetectedFonts(array $payload): array
    {
        $html = self::sanitizeInlineFontFamilies((string) ($payload['html'] ?? ''));
        $css = self::sanitizeComposerCss((string) ($payload['css'] ?? ''));
        $css = self::syncIdFontRulesFromInlineHtml($css, $html);
        $ids = Voodbuilder::fonts()->detectUsedIds($css."\n".$html);
        $payload['html'] = $html;
        $payload['css'] = $css;
        $payload['fonts'] = $ids;

        return $payload;
    }

    /**
     * Normalize font-family stacks in composer CSS and collapse duplicate #id rules
     * so the last author paint wins (avoids editor vs published cascade mismatches).
     */
    public static function sanitizeComposerCss(string $css): string
    {
        if ($css === '') {
            return $css;
        }

        $normalized = preg_replace_callback(
            '/font-family\s*:\s*([^;{}]+)/i',
            static function (array $matches): string {
                $raw = trim($matches[1]);
                $important = (bool) preg_match('/!important\s*$/i', $raw);
                $stack = FontDefinition::cssSafeStack((string) preg_replace('/\s*!important\s*$/i', '', $raw));

                return 'font-family: '.$stack.($important ? ' !important' : '');
            },
            $css,
        );

        if (! is_string($normalized)) {
            return $css;
        }

        return self::collapseDuplicateIdRules($normalized);
    }

    /**
     * When an element has both inline font-family and a #id CSS rule, make the
     * #id rule match the inline value so editor canvas and published page agree.
     */
    public static function syncIdFontRulesFromInlineHtml(string $css, string $html): string
    {
        if ($html === '' || ! str_contains($html, 'font-family')) {
            return $css;
        }

        /** @var array<string, string> $inlineFonts */
        $inlineFonts = [];

        $patterns = [
            '/\bid=("|\')([^"\']+)\1[^>]*\bstyle="([^"]*font-family[^"]*)"/i',
            '/\bid=("|\')([^"\']+)\1[^>]*\bstyle=\'([^\']*font-family[^\']*)\'/i',
            '/\bstyle="([^"]*font-family[^"]*)"[^>]*\bid=("|\')([^"\']+)\2/i',
            '/\bstyle=\'([^\']*font-family[^\']*)\'[^>]*\bid=("|\')([^"\']+)\2/i',
        ];

        foreach ($patterns as $index => $pattern) {
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) < 1) {
                continue;
            }

            foreach ($matches as $match) {
                if ($index < 2) {
                    $id = (string) ($match[2] ?? '');
                    $style = (string) ($match[3] ?? '');
                } else {
                    $style = (string) ($match[1] ?? '');
                    $id = (string) ($match[3] ?? '');
                }

                if ($id === '' || $style === '' || ! preg_match('/font-family\s*:\s*([^;]+)/i', $style, $fontMatch)) {
                    continue;
                }

                $stack = FontDefinition::cssSafeStack(
                    (string) preg_replace('/\s*!important\s*$/i', '', trim($fontMatch[1])),
                );

                if ($stack === '') {
                    continue;
                }

                $inlineFonts[$id] = $stack.' !important';
            }
        }

        if ($inlineFonts === []) {
            return $css;
        }

        foreach ($inlineFonts as $id => $stack) {
            $pattern = '/#'.preg_quote($id, '/').'(?![\w-])\s*\{([^{}]*)\}/';
            $replaced = false;
            $css = (string) preg_replace_callback(
                $pattern,
                static function (array $m) use ($id, $stack, &$replaced): string {
                    if ($replaced) {
                        return $m[0];
                    }

                    $replaced = true;
                    $body = trim($m[1]);
                    $withoutFont = trim((string) preg_replace('/(?:^|;)\s*font-family\s*:[^;]*/i', '', $body), '; ');
                    $next = ($withoutFont !== '' ? $withoutFont.'; ' : '').'font-family: '.$stack;

                    return '#'.$id.' {'.$next.';}';
                },
                $css,
            );

            if (! $replaced) {
                $css = '#'.$id.' {font-family: '.$stack.';}'."\n".$css;
            }
        }

        return self::collapseDuplicateIdRules($css);
    }

    /**
     * Keep font-family values safe inside HTML style="..." attributes.
     *
     * Double-quoted CSS families like font-family: "Fira Code" break the attribute
     * into style="font-family:" fira="" code="" … — repair that and normalize quotes.
     */
    public static function sanitizeInlineFontFamilies(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $repaired = self::repairBrokenFontFamilyAttributes($html);
        $repaired = self::stripOrphanedFontStackAttributes($repaired);

        if (! str_contains($repaired, 'font-family')) {
            return $repaired;
        }

        // Intact style="…font-family: …" → single quotes inside the attribute.
        $normalized = preg_replace_callback(
            '/\sstyle=(")([^"]*)\1/i',
            static function (array $matches): string {
                $value = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5);

                if (! str_contains(strtolower($value), 'font-family')) {
                    return $matches[0];
                }

                $safeStyle = preg_replace_callback(
                    '/font-family\s*:\s*([^;]+)/i',
                    static function (array $m): string {
                        $raw = trim($m[1]);
                        $important = (bool) preg_match('/!important\s*$/i', $raw);
                        $stack = FontDefinition::cssSafeStack((string) preg_replace('/\s*!important\s*$/i', '', $raw));

                        return 'font-family: '.$stack.($important ? ' !important' : '');
                    },
                    $value,
                );

                if (! is_string($safeStyle)) {
                    return $matches[0];
                }

                return ' style="'.$safeStyle.'"';
            },
            $repaired,
        );

        return is_string($normalized) ? $normalized : $repaired;
    }

    /**
     * Repair style="font-family:" name="" parts="" … back into a valid declaration.
     */
    public static function repairBrokenFontFamilyAttributes(string $html): string
    {
        if (! str_contains($html, 'font-family:"') && ! str_contains($html, "font-family:'")) {
            // Still may have orphaned tokens without a leftover style= fragment.
            return $html;
        }

        $generics = self::fontStackGenericTokens();

        $repaired = preg_replace_callback(
            '/\sstyle="font-family:"((?:\s+[A-Za-z][A-Za-z0-9_-]*="")+)/',
            static function (array $matches) use ($generics): string {
                if (preg_match_all('/\s+([A-Za-z][A-Za-z0-9_-]*)=""/', $matches[1], $tokenMatches) < 1) {
                    return $matches[0];
                }

                /** @var list<string> $tokens */
                $tokens = array_values($tokenMatches[1] ?? []);

                if ($tokens === []) {
                    return $matches[0];
                }

                return ' style="font-family: '.self::stackFromBrokenTokens($tokens, $generics).'"';
            },
            $html,
        );

        return is_string($repaired) ? $repaired : $html;
    }

    /**
     * After a broken style="font-family:"Exo 2"…" parse, orphans like exo="" remain
     * even if a later Literata style= was applied. Strip them; rebuild style only
     * when the tag has no font-family yet.
     */
    public static function stripOrphanedFontStackAttributes(string $html): string
    {
        if ($html === '' || ! str_contains($html, '=""')) {
            return $html;
        }

        $generics = self::fontStackGenericTokens();
        $fontWordTokens = self::catalogFontWordTokens();

        $replaced = preg_replace_callback(
            '/<([a-zA-Z][\w:-]*)(\s[^>]*?)(\/?)>/',
            static function (array $matches) use ($generics, $fontWordTokens): string {
                $attrs = $matches[2];

                if (! str_contains($attrs, '=""')) {
                    return $matches[0];
                }

                if (preg_match_all('/\s+([A-Za-z][A-Za-z0-9_-]*)=""/', $attrs, $emptyMatches) < 1) {
                    return $matches[0];
                }

                /** @var list<string> $orphanNames */
                $orphanNames = [];

                foreach ($emptyMatches[1] as $name) {
                    $lower = strtolower($name);

                    if (in_array($lower, $generics, true) || isset($fontWordTokens[$lower])) {
                        $orphanNames[] = $name;
                    }
                }

                if ($orphanNames === []) {
                    return $matches[0];
                }

                $clean = $attrs;

                foreach ($orphanNames as $name) {
                    $clean = (string) preg_replace('/\s+'.preg_quote($name, '/').'=""/i', '', $clean);
                }

                // Never invent a font-family from leftovers — CSS #id rules are the
                // source of truth. Reconstruct only via repairBrokenFontFamilyAttributes
                // when a broken style="font-family:" fragment is still present.

                return '<'.$matches[1].$clean.$matches[3].'>';
            },
            $html,
        );

        return is_string($replaced) ? $replaced : $html;
    }

    /**
     * Merge repeated `#id { … }` rules so later declarations win per property.
     * Leaves class/utility rules untouched.
     */
    public static function collapseDuplicateIdRules(string $css): string
    {
        if ($css === '' || ! str_contains($css, '#')) {
            return $css;
        }

        if (preg_match_all('/#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/', $css, $matches, PREG_OFFSET_CAPTURE) < 1) {
            return $css;
        }

        /** @var array<string, array{props: array<string, string>, first: int, spans: list<array{0: int, 1: int}>}> $byId */
        $byId = [];
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $full = $matches[0][$i][0];
            $offset = $matches[0][$i][1];
            $id = $matches[1][$i][0];
            $body = $matches[2][$i][0];

            if (! isset($byId[$id])) {
                $byId[$id] = [
                    'props' => [],
                    'first' => $offset,
                    'spans' => [],
                ];
            }

            $byId[$id]['spans'][] = [$offset, strlen($full)];

            foreach (preg_split('/\s*;\s*/', trim($body)) ?: [] as $declaration) {
                $declaration = trim($declaration);

                if ($declaration === '' || ! str_contains($declaration, ':')) {
                    continue;
                }

                [$property, $value] = array_map('trim', explode(':', $declaration, 2));
                $propertyKey = strtolower($property);

                if ($propertyKey === '') {
                    continue;
                }

                $byId[$id]['props'][$propertyKey] = $property.': '.$value;
            }
        }

        /** @var list<array{0: int, 1: int, 2: string}> $ops */
        $ops = [];

        foreach ($byId as $id => $entry) {
            if (count($entry['spans']) < 2) {
                continue;
            }

            $merged = '#'.$id.' {'.implode('; ', array_values($entry['props'])).';}';

            foreach ($entry['spans'] as [$start, $length]) {
                $ops[] = [$start, $length, $start === $entry['first'] ? $merged : ''];
            }
        }

        if ($ops === []) {
            return $css;
        }

        usort($ops, static fn (array $a, array $b): int => $b[0] <=> $a[0]);

        $result = $css;

        foreach ($ops as [$start, $length, $replacement]) {
            $result = substr($result, 0, $start).$replacement.substr($result, $start + $length);
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $result));
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<string>  $generics
     */
    private static function stackFromBrokenTokens(array $tokens, array $generics): string
    {
        $familyWords = [];
        $fallbacks = [];
        $inFallback = false;

        foreach ($tokens as $token) {
            $lower = strtolower($token);

            if ($inFallback || in_array($lower, $generics, true)) {
                $inFallback = true;
                $fallbacks[] = $token;

                continue;
            }

            $familyWords[] = $token;
        }

        if ($familyWords === []) {
            return 'sans-serif';
        }

        $family = implode(' ', array_map(
            static fn (string $word): string => str_replace('-', ' ', ucwords($word, '-')),
            $familyWords,
        ));
        $family = str_replace(
            ['Ibm ', 'Dm ', 'Eb ', 'Ui '],
            ['IBM ', 'DM ', 'EB ', 'UI '],
            $family,
        );

        $resolved = self::resolveCatalogFamily($family);
        $stack = "'{$resolved}'";

        if ($fallbacks !== []) {
            $stack .= ', '.implode(', ', $fallbacks);
        }

        return FontDefinition::cssSafeStack($stack);
    }

    private static function resolveCatalogFamily(string $family): string
    {
        $needle = strtolower(trim($family));

        if ($needle === '') {
            return $family;
        }

        foreach (Voodbuilder::fonts()->all() as $font) {
            $candidate = strtolower($font->family);

            if ($candidate === $needle || str_starts_with($candidate, $needle)) {
                return $font->family;
            }
        }

        // "Exo" from broken "Exo 2" (digit dropped as invalid attr name).
        foreach (Voodbuilder::fonts()->all() as $font) {
            $candidate = strtolower($font->family);
            $firstWord = explode(' ', $candidate)[0] ?? '';

            if ($firstWord !== '' && $firstWord === $needle) {
                return $font->family;
            }
        }

        return $family;
    }

    /**
     * @return list<string>
     */
    private static function fontStackGenericTokens(): array
    {
        return [
            'ui-sans-serif', 'ui-serif', 'ui-monospace', 'system-ui',
            'sans-serif', 'serif', 'monospace', 'cursive', 'fantasy',
            'georgia', 'helvetica', 'arial', 'courier',
        ];
    }

    /**
     * @return array<string, true>
     */
    private static function catalogFontWordTokens(): array
    {
        $tokens = [];

        foreach (Voodbuilder::fonts()->all() as $font) {
            foreach (preg_split('/\s+/', $font->family) ?: [] as $word) {
                $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $word));

                if ($slug !== '' && ! is_numeric($slug)) {
                    $tokens[$slug] = true;
                }
            }
        }

        return $tokens;
    }
}
