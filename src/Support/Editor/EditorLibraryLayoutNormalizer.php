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

    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'voodbuilder-slider')) {
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
