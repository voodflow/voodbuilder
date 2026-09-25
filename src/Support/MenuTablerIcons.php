<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Tabler Icons for navigation menus (Filament picker + front-end SVG).
 *
 * Values are stored as `icon-name` (outline) or `icon-name:filled`.
 * Paths come from the editor catalog built from @tabler/icons.
 */
final class MenuTablerIcons
{
    public const STYLE_OUTLINE = 'outline';

    public const STYLE_FILLED = 'filled';

    private const FILLED_SUFFIX = ':filled';

    /** @var array{outline: array<string, string>, filled: array<string, string>, tags: array<string, string>}|null */
    private static ?array $catalog = null;

    /**
     * Tiny fallback when the JSON catalog is missing (tests / broken installs).
     *
     * @var array<string, string> name => SVG inner HTML
     */
    private const FALLBACK_OUTLINE = [
        'brand-facebook' => '<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>',
        'brand-x' => '<path d="M4 4l11.733 16h4.267l-11.733 -16z"/><path d="M4 20l6.768 -6.768m2.46 -2.46l7.772 -7.772"/>',
        'brand-instagram' => '<path d="M4 4m0 4a4 4 0 014 -4h8a4 4 0 014 4v8a4 4 0 01-4 4h-8a4 4 0 01-4 -4z"/><path d="M12 12m-3 0a3 3 0 106 0a3 3 0 10-6 0"/><path d="M16.5 7.5l0 .01"/>',
        'brand-linkedin' => '<path d="M4 4m0 2a2 2 0 012 -2h12a2 2 0 012 2v12a2 2 0 01-2 2h-12a2 2 0 01-2 -2z"/><path d="M8 11l0 5"/><path d="M8 8l0 .01"/><path d="M12 16l0 -5"/><path d="M16 16v-5a2 2 0 10 -4 0"/>',
        'brand-youtube' => '<path d="M2 8a4 4 0 014 -4h12a4 4 0 014 4v8a4 4 0 01-4 4h-12a4 4 0 01-4 -4v-8z"/><path d="M10 9l6 3l-6 3z"/>',
        'brand-github' => '<path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 00-1.3 -3.2a4.2 4.2 0 00-.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 00-6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 00-.1 3.2a4.6 4.6 0 00-1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"/>',
        'mail' => '<path d="M3 7a2 2 0 012 -2h14a2 2 0 012 2v10a2 2 0 01-2 2h-14a2 2 0 01-2 -2v-10z"/><path d="M3 7l9 6l9 -6"/>',
        'world' => '<path d="M3 12a9 9 0 1018 0a9 9 0 00-18 0"/><path d="M3.6 9h16.8"/><path d="M3.6 15h16.8"/><path d="M12 3a17 17 0 000 18"/><path d="M12 3a17 17 0 010 18"/>',
        'link' => '<path d="M9 15l6 -6"/><path d="M11 6l.463 -.536a5 5 0 017.072 0a4.993 4.993 0 011.193 5.435l-2.667 5.333"/><path d="M13 18l-.397 .534a5.068 5.068 0 01-7.127 0a4.973 4.973 0 010 -7.071l2.667 -5.334"/>',
        'home' => '<path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 002 2h10a2 2 0 002 -2v-7"/><path d="M10 12h4v4h-4z"/>',
        'layout-dashboard' => '<path d="M4 4h6v8h-6z"/><path d="M14 4h6v5h-6z"/><path d="M14 13h6v7h-6z"/><path d="M4 16h6v4h-6z"/>',
        'stack-2' => '<path d="M12 4l-8 4l8 4l8 -4l-8 -4"/><path d="M4 12l8 4l8 -4"/><path d="M4 16l8 4l8 -4"/>',
        'book-2' => '<path d="M19 4v16h-12a2 2 0 01-2 -2v-12a2 2 0 012 -2h12z"/><path d="M19 16h-12a2 2 0 00-2 2"/><path d="M9 8h6"/>',
        'school' => '<path d="M22 9l-10 -4l-10 4l10 4l10 -4v6"/><path d="M6 10.6v5.4a6 3 0 0012 0v-5.4"/>',
        'cookie' => '<path d="M8 13v.01"/><path d="M12 17v.01"/><path d="M12 12v.01"/><path d="M16 14v.01"/><path d="M11 8v.01"/><path d="M13.148 3.476l2.667 1.104a4 4 0 014.525 1.173l.15 .196l1.105 2.667a4 4 0 01.385 2.287l-.087 .433l.087 .433a4 4 0 01-.385 2.287l-1.105 2.667a4 4 0 01-4.525 1.173l-.196 -.15l-2.667 -1.105a4 4 0 01-2.287 -.385l-.433 -.087l-.433 .087a4 4 0 01-2.287 .385l-2.667 1.105a4 4 0 01-4.525 -1.173l-.15 -.196l-1.105 -2.667a4 4 0 01-.385 -2.287l.087 -.433l-.087 -.433a4 4 0 01.385 -2.287l1.105 -2.667a4 4 0 014.525 -1.173l.196 .15l2.667 1.105a4 4 0 012.287 .385l.433 .087l.433 -.087a4 4 0 012.287 -.385z"/>',
        'coin' => '<path d="M12 12m-9 0a9 9 0 1018 0a9 9 0 10-18 0"/><path d="M14.8 9a2 2 0 00-1.8 -1h-2a2 2 0 100 4h2a2 2 0 110 4h-2a2 2 0 01-1.8 -1"/><path d="M12 7v10"/>',
        'photo' => '<path d="M15 8h.01"/><path d="M3 6a3 3 0 013 -3h12a3 3 0 013 3v12a3 3 0 01-3 3h-12a3 3 0 01-3 -3z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/>',
        'news' => '<path d="M16 6h3a1 1 0 011 1v11a2 2 0 01-4 0v-13a1 1 0 011 -1h3"/><path d="M4 4m0 1a1 1 0 011 -1h8a1 1 0 011 1v12a1 1 0 01-1 1h-8a1 1 0 01-1 -1z"/><path d="M8 8h4"/><path d="M8 12h4"/><path d="M8 16h4"/>',
        'list-details' => '<path d="M13 5h8"/><path d="M13 9h5"/><path d="M13 15h8"/><path d="M13 19h5"/><path d="M3 4m0 1a1 1 0 011 -1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1 -1z"/><path d="M3 14m0 1a1 1 0 011 -1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1 -1z"/>',
    ];

    /**
     * @return array{name: string, style: self::STYLE_*}
     */
    public static function parse(?string $value): array
    {
        $raw = strtolower(trim((string) $value));

        if ($raw === '') {
            return ['name' => '', 'style' => self::STYLE_OUTLINE];
        }

        if (str_ends_with($raw, self::FILLED_SUFFIX)) {
            return [
                'name' => substr($raw, 0, -strlen(self::FILLED_SUFFIX)),
                'style' => self::STYLE_FILLED,
            ];
        }

        return ['name' => $raw, 'style' => self::STYLE_OUTLINE];
    }

    public static function key(string $name, string $style = self::STYLE_OUTLINE): string
    {
        $name = strtolower(trim($name));

        return $style === self::STYLE_FILLED
            ? $name . self::FILLED_SUFFIX
            : $name;
    }

    public static function has(string $value): bool
    {
        ['name' => $name, 'style' => $style] = self::parse($value);

        if ($name === '') {
            return false;
        }

        return self::inner($name, $style) !== null;
    }

    /**
     * SVG inner markup for the icon (legacy alias: single-path icons used `path()`).
     */
    public static function path(string $value): ?string
    {
        ['name' => $name, 'style' => $style] = self::parse($value);

        return self::inner($name, $style);
    }

    public static function inner(string $name, string $style = self::STYLE_OUTLINE): ?string
    {
        $name = strtolower(trim($name));

        if ($name === '') {
            return null;
        }

        $catalog = self::catalog();

        if ($style === self::STYLE_FILLED) {
            $filled = $catalog['filled'][$name] ?? null;

            if (is_string($filled) && $filled !== '') {
                return $filled;
            }

            return null;
        }

        $outline = $catalog['outline'][$name] ?? null;

        if (is_string($outline) && $outline !== '') {
            return $outline;
        }

        return self::FALLBACK_OUTLINE[$name] ?? null;
    }

    public static function label(string $value): string
    {
        ['name' => $name, 'style' => $style] = self::parse($value);

        if ($name === '') {
            return '';
        }

        $base = str($name)->replace('-', ' ')->title()->toString();

        return $style === self::STYLE_FILLED
            ? $base . ' · ' . __('voodbuilder::admin.navigation.icon_style_filled')
            : $base . ' · ' . __('voodbuilder::admin.navigation.icon_style_outline');
    }

    public static function svgHtml(string $value, string $class = 'h-5 w-5'): string
    {
        ['name' => $name, 'style' => $style] = self::parse($value);
        $inner = self::inner($name, $style);

        if ($inner === null) {
            return '';
        }

        $classAttr = e($class);

        if ($style === self::STYLE_FILLED) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none" class="'
                . $classAttr . '" aria-hidden="true">' . $inner . '</svg>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="'
            . $classAttr . '" aria-hidden="true">' . $inner . '</svg>';
    }

    public static function optionHtml(string $value): string
    {
        $svg = self::svgHtml($value, 'h-5 w-5 shrink-0');
        $label = e(self::label($value));

        return '<span class="inline-flex items-center gap-2">'
            . '<span class="fi-icon-btn-icon text-gray-500 dark:text-gray-400">' . $svg . '</span>'
            . '<span>' . $label . '</span>'
            . '</span>';
    }

    /**
     * Static options for small curated lists (social fallback). Prefer searchResults().
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];

        foreach (array_keys(self::FALLBACK_OUTLINE) as $name) {
            $key = self::key($name, self::STYLE_OUTLINE);
            $out[$key] = self::optionHtml($key);
        }

        asort($out);

        return $out;
    }

    /**
     * Filament Select search results (HTML labels, outline + filled when available).
     *
     * @return array<string, string>
     */
    public static function searchResults(string $search, int $limit = 40): array
    {
        $search = strtolower(trim($search));
        $catalog = self::catalog();
        $out = [];

        if ($search === '') {
            $preferred = [
                'home', 'layout-dashboard', 'stack-2', 'apps', 'book-2', 'school',
                'news', 'photo', 'cookie', 'coin', 'shield-lock', 'settings',
                'brand-facebook', 'brand-x', 'brand-instagram', 'brand-linkedin',
                'brand-youtube', 'brand-github', 'mail', 'world', 'link',
            ];
            $names = array_values(array_filter(
                $preferred,
                fn (string $name): bool => isset($catalog['outline'][$name]) || isset(self::FALLBACK_OUTLINE[$name]),
            ));

            if ($names === []) {
                $names = array_slice(array_keys($catalog['outline']), 0, $limit);
            }
        } else {
            $exact = [];
            $prefix = [];
            $contains = [];
            $tagged = [];

            foreach (array_keys($catalog['outline']) as $name) {
                $label = str_replace('-', ' ', $name);

                if ($name === $search || $label === $search) {
                    $exact[] = $name;

                    continue;
                }

                if (str_starts_with($name, $search) || str_starts_with($label, $search)) {
                    $prefix[] = $name;

                    continue;
                }

                if (str_contains($name, $search) || str_contains($label, $search)) {
                    $contains[] = $name;

                    continue;
                }

                $tags = strtolower((string) ($catalog['tags'][$name] ?? ''));

                if ($tags !== '' && str_contains($tags, $search)) {
                    $tagged[] = $name;
                }
            }

            sort($exact);
            sort($prefix);
            sort($contains);
            sort($tagged);

            $names = array_values(array_unique([...$exact, ...$prefix, ...$contains, ...$tagged]));
        }

        foreach ($names as $name) {
            if (count($out) >= $limit) {
                break;
            }

            $outlineKey = self::key($name, self::STYLE_OUTLINE);

            if (self::has($outlineKey)) {
                $out[$outlineKey] = self::optionHtml($outlineKey);
            }

            if (count($out) >= $limit) {
                break;
            }

            $filledKey = self::key($name, self::STYLE_FILLED);

            if (self::has($filledKey)) {
                $out[$filledKey] = self::optionHtml($filledKey);
            }
        }

        return $out;
    }

    public static function flushCatalogCache(): void
    {
        self::$catalog = null;
    }

    /**
     * @return array{outline: array<string, string>, filled: array<string, string>, tags: array<string, string>}
     */
    private static function catalog(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }

        $path = dirname(__DIR__, 2) . '/resources/js/editor/generated/tabler-icons-full.json';

        if (! is_file($path)) {
            self::$catalog = [
                'outline' => self::FALLBACK_OUTLINE,
                'filled' => [],
                'tags' => [],
            ];

            return self::$catalog;
        }

        /** @var array{outline?: array<string, string>, filled?: array<string, string>, tags?: array<string, string>}|null $decoded */
        $decoded = json_decode((string) file_get_contents($path), true);

        self::$catalog = [
            'outline' => is_array($decoded['outline'] ?? null) ? $decoded['outline'] : self::FALLBACK_OUTLINE,
            'filled' => is_array($decoded['filled'] ?? null) ? $decoded['filled'] : [],
            'tags' => is_array($decoded['tags'] ?? null) ? $decoded['tags'] : [],
        ];

        return self::$catalog;
    }
}
