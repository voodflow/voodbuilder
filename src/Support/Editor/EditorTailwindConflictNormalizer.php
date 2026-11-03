<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Keeps one utility per CSS property inside a `class` attribute.
 *
 * Tailwind emits every utility it sees, sorted by its own internal order — not by
 * the order the classes appear in the attribute. Two conflicting utilities
 * (`text-white text-vp-brand-2`) therefore resolve by stylesheet order, which can
 * differ between the editor canvas (live JIT appends the newest rule last) and the
 * published page (compiled sheet). Authors see the colour they picked in the
 * editor and a different one on the site.
 *
 * Collapsing the conflict at serialization time (last one wins, matching what the
 * editor paints) makes the stored markup unambiguous and the two renders agree.
 */
final class EditorTailwindConflictNormalizer
{
    /**
     * Colour families that never coexist on the same variant.
     *
     * @var array<string, string>
     */
    private const COLOR_PATTERNS = [
        'text-color' => '/^text-(?:white|black|transparent|current|inherit)$/',
        'bg-color' => '/^bg-(?:white|black|transparent|current|inherit)$/',
        'border-color' => '/^border-(?:white|black|transparent|current|inherit)$/',
        'ring-color' => '/^ring-(?:white|black|transparent|current|inherit)$/',
        'divide-color' => '/^divide-(?:white|black|transparent|current|inherit)$/',
        'outline-color' => '/^outline-(?:white|black|transparent|current|inherit)$/',
    ];

    /**
     * @var list<string>
     */
    private const PALETTE_SHADES = [
        'slate', 'gray', 'zinc', 'neutral', 'stone', 'red', 'orange', 'amber',
        'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue',
        'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose',
    ];

    public static function normalizeHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'class')) {
            return $html;
        }

        $normalized = preg_replace_callback(
            '/\bclass=(["\'])(.*?)\1/is',
            static fn (array $matches): string => 'class=' . $matches[1]
                . self::normalizeClassList($matches[2])
                . $matches[1],
            $html,
        );

        return is_string($normalized) ? $normalized : $html;
    }

    /**
     * Drop every utility that a later token of the same family + variant overrides.
     */
    public static function normalizeClassList(string $classes): string
    {
        $tokens = preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($tokens) || $tokens === []) {
            return trim($classes);
        }

        /** @var array<string, list<int>> $slots */
        $slots = [];
        $seen = [];

        foreach ($tokens as $index => $token) {
            $slot = self::slotFor($token);

            if ($slot === null) {
                continue;
            }

            $slots[$slot][] = $index;
        }

        $drop = [];

        foreach ($slots as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            // Keep the last occurrence — the one the editor paints on top.
            array_pop($indexes);

            foreach ($indexes as $index) {
                $drop[$index] = true;
            }
        }

        $kept = [];

        foreach ($tokens as $index => $token) {
            if (isset($drop[$index])) {
                continue;
            }

            // Collapse exact duplicates (`class="a a"`).
            if (isset($seen[$token])) {
                continue;
            }

            $seen[$token] = true;
            $kept[] = $token;
        }

        return implode(' ', $kept);
    }

    /**
     * `dark:md:hover:text-white` → "dark:md:hover|text-color".
     */
    private static function slotFor(string $token): ?string
    {
        if ($token === '' || $token[0] === '.') {
            return null;
        }

        $prefix = '';
        $utility = $token;

        if (preg_match('/^((?:(?:[\w-]+|\[[^\]]*\])+:)+)(.+)$/', $token, $matches) === 1) {
            $prefix = $matches[1];
            $utility = $matches[2];
        }

        // `!important` markers are not part of the utility name.
        $utility = ltrim($utility, '!');

        if ($utility === '') {
            return null;
        }

        $family = self::familyFor($utility);

        return $family === null ? null : $prefix . '|' . $family;
    }

    private static function familyFor(string $utility): ?string
    {
        foreach (self::COLOR_PATTERNS as $family => $pattern) {
            if (preg_match($pattern, $utility) === 1) {
                return $family;
            }
        }

        if (preg_match('/^(text|bg|border|ring|divide|outline)-vp-[\w-]+$/', $utility, $matches) === 1) {
            return $matches[1] . '-color';
        }

        if (preg_match('/^(text|bg|border|ring|divide|outline)-(' . implode('|', self::PALETTE_SHADES) . ')-\d{2,3}(?:\/\d{1,3})?$/', $utility, $matches) === 1) {
            return $matches[1] . '-color';
        }

        if (preg_match('/^(text|bg|border|ring|divide|outline)-\[(.+)\]$/', $utility, $matches) === 1
            && self::looksLikeColor($matches[2])) {
            return $matches[1] . '-color';
        }

        return null;
    }

    private static function looksLikeColor(string $value): bool
    {
        return preg_match('/^(?:#|rgba?\(|hsla?\(|oklch\(|oklab\(|hwb\(|lab\(|lch\(|color:|var\()/i', trim($value)) === 1;
    }
}
