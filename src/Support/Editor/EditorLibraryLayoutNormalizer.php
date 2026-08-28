<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Ensures library structural hooks carry the Tailwind utilities the page JIT
 * must compile for editor ≈ public parity.
 *
 * Custom BEM classes (voodbuilder-slider__track, …) are JS/selectors only —
 * they never appear in JIT output. Layout belongs on utility tokens in HTML.
 * This normalizer is the general rule: map hook → required utilities, merge
 * idempotently on catalog prepare, save, and public render/CSS resolve.
 */
final class EditorLibraryLayoutNormalizer
{
    /**
     * Hook class => required Tailwind utility classes (presentation).
     *
     * @var array<string, list<string>>
     */
    private const HOOK_UTILITIES = [
        'voodbuilder-slider__track' => [
            'flex',
            'gap-4',
            'overflow-x-auto',
            'snap-x',
            'snap-mandatory',
            'scroll-smooth',
            '[scrollbar-width:none]',
        ],
        'voodbuilder-slider__slide' => [
            'w-[85%]',
            'shrink-0',
            'snap-start',
            'snap-always',
            'md:w-2/5',
        ],
        'voodbuilder-slider__poster' => [
            'relative',
            'aspect-[4/3]',
            'overflow-hidden',
        ],
    ];

    /**
     * Layout Container presets → Tailwind grid-cols utility.
     * Mirrors packages/.../layout-blocks.js LAYOUT_PRESETS.colsClass.
     *
     * @var array<string, string>
     */
    private const LAYOUT_PRESET_COLS = [
        '1' => 'grid-cols-1',
        '2' => 'grid-cols-2',
        '3' => 'grid-cols-3',
        '4' => 'grid-cols-4',
        '6' => 'grid-cols-6',
        '1-2' => 'grid-cols-[minmax(0,1fr)_minmax(0,2fr)]',
        '2-1' => 'grid-cols-[minmax(0,2fr)_minmax(0,1fr)]',
        '1-3' => 'grid-cols-[minmax(0,1fr)_minmax(0,3fr)]',
        '3-1' => 'grid-cols-[minmax(0,3fr)_minmax(0,1fr)]',
        '1-1-2' => 'grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,2fr)]',
        '2-1-1' => 'grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]',
        '1-2-1' => 'grid-cols-[minmax(0,1fr)_minmax(0,2fr)_minmax(0,1fr)]',
    ];

    /**
     * @var list<string>
     */
    private const LAYOUT_BASE_UTILITIES = [
        'w-full',
        'vb-layout-row',
        'grid',
        'gap-4',
    ];

    public static function normalize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $needsSlider = str_contains($html, 'voodbuilder-slider');
        $needsLayout = str_contains($html, 'data-vb-layout-preset')
            || str_contains($html, 'data-voodbuilder-layout="container"')
            || str_contains($html, "data-voodbuilder-layout='container'");

        if (! $needsSlider && ! $needsLayout) {
            return $html;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        if (! $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="vb-lib-layout-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        )) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return $html;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('vb-lib-layout-root');

        if (! $root instanceof DOMElement) {
            return $html;
        }

        $xpath = new DOMXPath($document);
        $changed = false;

        if ($needsSlider) {
            foreach (self::HOOK_UTILITIES as $hook => $utilities) {
                foreach ($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " '.$hook.' ")]', $root) as $node) {
                    if (! $node instanceof DOMElement) {
                        continue;
                    }

                    if (self::mergeUtilities($node, $utilities)) {
                        $changed = true;
                    }
                }
            }

            // Legacy video-slider posters: relative+overflow without aspect utility.
            foreach ($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " voodbuilder-slider__slide ")]//article/*[contains(concat(" ", normalize-space(@class), " "), " relative ") and contains(concat(" ", normalize-space(@class), " "), " overflow-hidden ")]', $root) as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                if (self::mergeUtilities($node, ['aspect-[4/3]'])) {
                    $changed = true;
                }
            }
        }

        if ($needsLayout) {
            foreach ($xpath->query('.//*[@data-vb-layout-preset or (@data-voodbuilder-layout="container" and @data-vb-layout-tracks)]', $root) as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                if (self::normalizeLayoutContainer($node)) {
                    $changed = true;
                }
            }
        }

        if (! $changed) {
            return $html;
        }

        $normalized = '';

        foreach ($root->childNodes as $child) {
            $normalized .= $document->saveHTML($child);
        }

        return $normalized !== '' ? $normalized : $html;
    }

    /**
     * Tailwind-first layout containers: ensure grid-cols-* / strip redundant layout inline styles.
     */
    private static function normalizeLayoutContainer(DOMElement $element): bool
    {
        $changed = false;
        $presetId = trim($element->getAttribute('data-vb-layout-preset'));
        $tracks = trim($element->getAttribute('data-vb-layout-tracks'));
        $colsClass = self::LAYOUT_PRESET_COLS[$presetId] ?? self::tracksToGridColsClass($tracks);

        $required = [...self::LAYOUT_BASE_UTILITIES, $colsClass];

        // Keep author gap-* if present — only inject default when missing.
        $existing = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];
        $existing = array_values(array_filter($existing, static fn (string $class): bool => $class !== ''));
        $hasGap = false;

        foreach ($existing as $token) {
            if (preg_match('/^(?:sm:|md:|lg:|xl:|2xl:)?gap-/', $token) === 1) {
                $hasGap = true;
                break;
            }
        }

        if ($hasGap) {
            $required = array_values(array_filter(
                $required,
                static fn (string $utility): bool => $utility !== 'gap-4',
            ));
        }

        $hasContentWidth = in_array(
            trim($element->getAttribute('data-voodbuilder-content-width')),
            ['normal', 'custom', 'full'],
            true,
        );

        // Drop previous grid-cols-* (and boxed measure when content-width does not own it).
        $withoutCols = array_values(array_filter(
            $existing,
            static function (string $token) use ($hasContentWidth): bool {
                if (preg_match('/^(?:sm:|md:|lg:|xl:|2xl:)?grid-cols-/', $token) === 1) {
                    return false;
                }

                if ($hasContentWidth) {
                    return true;
                }

                return ! self::isBoxedOrWidthUtility($token);
            },
        ));

        if ($withoutCols !== $existing) {
            $element->setAttribute('class', implode(' ', $withoutCols));
            $changed = true;
        }

        if (self::mergeUtilities($element, $required)) {
            $changed = true;
        }

        if (self::stripLayoutMechanicInlineStyles($element)) {
            $changed = true;
        }

        return $changed;
    }

    private static function tracksToGridColsClass(string $tracks): string
    {
        $parts = preg_split('/\s+/', trim($tracks)) ?: [];
        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => preg_replace('/\s+/', '', $part) ?? '',
            $parts,
        ), static fn (string $part): bool => $part !== ''));

        if ($parts === []) {
            return 'grid-cols-1';
        }

        $equalOneFr = true;

        foreach ($parts as $part) {
            if ($part !== 'minmax(0,1fr)' && $part !== '1fr') {
                $equalOneFr = false;
                break;
            }
        }

        $count = count($parts);

        if ($equalOneFr && $count >= 1 && $count <= 12) {
            return 'grid-cols-'.$count;
        }

        return 'grid-cols-['.implode('_', $parts).']';
    }

    /**
     * Remove display/grid/width mechanics that duplicate Tailwind utilities.
     * Leaves author paints (color, font-family, …) and content-width measure when set.
     */
    private static function stripLayoutMechanicInlineStyles(DOMElement $element): bool
    {
        $style = trim($element->getAttribute('style'));

        if ($style === '') {
            return false;
        }

        $hasContentWidth = in_array(
            trim($element->getAttribute('data-voodbuilder-content-width')),
            ['normal', 'custom', 'full'],
            true,
        );

        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(';', $style),
        ), static fn (string $part): bool => $part !== ''));

        $kept = [];

        foreach ($parts as $part) {
            if (preg_match('/^(display|grid-template-columns|grid-template-rows|--vb-layout-tracks|gap|column-gap|row-gap)\s*:/i', $part) === 1) {
                continue;
            }

            // Measure keys only when content-width toolbar does not own them.
            if (
                ! $hasContentWidth
                && preg_match('/^(width|max-width|maxWidth|margin-left|margin-right|margin-inline)\s*:/i', $part) === 1
            ) {
                continue;
            }

            $kept[] = $part;
        }

        if ($kept === $parts) {
            return false;
        }

        if ($kept === []) {
            $element->removeAttribute('style');
        } else {
            $element->setAttribute('style', implode('; ', $kept));
        }

        return true;
    }

    /**
     * @param  list<string>  $utilities
     */
    private static function mergeUtilities(DOMElement $element, array $utilities): bool
    {
        $existing = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];
        $existing = array_values(array_filter($existing, static fn (string $class): bool => $class !== ''));
        $set = array_fill_keys($existing, true);
        $changed = false;

        foreach ($utilities as $utility) {
            if (isset($set[$utility])) {
                continue;
            }

            // Skip if a conflicting width/aspect already exists (author override).
            if (self::hasConflictingUtility($existing, $utility)) {
                continue;
            }

            $existing[] = $utility;
            $set[$utility] = true;
            $changed = true;
        }

        if ($changed) {
            $element->setAttribute('class', implode(' ', $existing));
        }

        return $changed;
    }

    /**
     * Hardcoded boxed / width utilities — layout containers fill the parent slot instead.
     * Mirrors layout-blocks.js isBoxedOrWidthUtility (keeps w-full which we re-add).
     */
    private static function isBoxedOrWidthUtility(string $token): bool
    {
        $boxed = [
            'container',
            'mx-auto',
            'max-w-7xl',
            'max-w-6xl',
            'max-w-5xl',
            'max-w-4xl',
            'max-w-3xl',
            'max-w-2xl',
            'max-w-xl',
            'max-w-lg',
            'max-w-screen-xl',
            'max-w-screen-2xl',
            'px-4',
            'px-5',
            'px-6',
        ];

        if (in_array($token, $boxed, true)) {
            return true;
        }

        // Keep w-full — required layout base utility.
        if ($token === 'w-full') {
            return false;
        }

        if (preg_match('/^(?:sm:|md:|lg:|xl:|2xl:)?(?:w-|basis-|flex-|max-w-|min-w-)/', $token) === 1) {
            return true;
        }

        return preg_match('/^(?:sm:|md:|lg:|xl:|2xl:)?max-w-\[/', $token) === 1;
    }

    /**
     * @param  list<string>  $existing
     */
    private static function hasConflictingUtility(array $existing, string $utility): bool
    {
        if (str_starts_with($utility, 'aspect-') || str_starts_with($utility, 'aspect-[')) {
            foreach ($existing as $class) {
                if (str_starts_with($class, 'aspect-')) {
                    return true;
                }
            }
        }

        if (preg_match('/^(?:!?(?:sm|md|lg|xl|2xl):)?w-/', $utility) === 1) {
            $prefix = '';

            if (preg_match('/^(!?(?:sm|md|lg|xl|2xl):)/', $utility, $match) === 1) {
                $prefix = $match[1];
            }

            foreach ($existing as $class) {
                if ($prefix !== '' && str_starts_with($class, $prefix) && preg_match('/^'.preg_quote($prefix, '/').'w-/', $class) === 1) {
                    return true;
                }

                if ($prefix === '' && preg_match('/^(?:!?w-|w-\[)/', $class) === 1 && ! str_contains($class, ':')) {
                    return true;
                }
            }
        }

        return false;
    }
}
