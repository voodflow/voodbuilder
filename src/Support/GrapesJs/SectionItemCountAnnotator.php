<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Marks repeating item roots on section blocks so the editor can expose an item-count setting.
 *
 * @phpstan-type ItemCountRule array{root: string, item: string, min?: int, max?: int}
 */
final class SectionItemCountAnnotator
{
    /**
     * Blocks that only differed by item count (or duplicated a sibling layout).
     *
     * @var list<string>
     */
    public const REDUNDANT_BLOCK_IDS = [
        'vb-content-7', // Media · 2 cards → use vb-content-8 + item count
    ];

    /**
     * @var array<string, ItemCountRule>
     */
    private const RULES = [
        'vb-statistic-1' => [
            'root' => ".//*[contains(concat(' ', normalize-space(@class), ' '), ' flex ') and contains(@class, 'flex-wrap') and contains(@class, 'text-center')]",
            'item' => './div[contains(@class, "p-4")]',
            'min' => 2,
            'max' => 6,
        ],
        'vb-content-8' => [
            'root' => ".//*[contains(@class, 'flex-wrap') and contains(@class, '-mt-4')]",
            'item' => './div[contains(@class, "p-4")]',
            'min' => 2,
            'max' => 4,
        ],
        'vb-feature-1' => [
            'root' => ".//*[contains(@class, 'flex-wrap') and contains(@class, '-m-4')]",
            'item' => './div[contains(@class, "p-4")]',
            'min' => 2,
            'max' => 6,
        ],
        'vb-feature-4' => [
            'root' => ".//*[contains(@class, 'flex-wrap') and contains(@class, '-m-4')]",
            'item' => './div[contains(@class, "p-4")]',
            'min' => 2,
            'max' => 4,
        ],
        'vb-feature-7' => [
            'root' => ".//*[contains(@class, 'flex-wrap') and contains(@class, '-mx-2')]",
            'item' => './div[contains(@class, "p-2")]',
            'min' => 2,
            'max' => 8,
        ],
        'vb-team-1' => [
            'root' => ".//*[contains(@class, 'flex-wrap') and contains(@class, '-m-2')]",
            'item' => './div[contains(@class, "p-2")]',
            'min' => 2,
            'max' => 9,
        ],
        'vb-pricing-1' => [
            'root' => ".//*[contains(@class, 'flex-wrap') and contains(@class, '-m-4')]",
            'item' => './div[contains(@class, "p-4")]',
            'min' => 2,
            'max' => 4,
        ],
        'vb-ecommerce-1' => [
            'root' => ".//*[contains(@class, 'flex-wrap') and contains(@class, '-m-4')]",
            'item' => './div[contains(@class, "p-4")]',
            'min' => 2,
            'max' => 8,
        ],
    ];

    public static function isRedundant(string $blockId): bool
    {
        return in_array($blockId, self::REDUNDANT_BLOCK_IDS, true);
    }

    /**
     * @return array<string, ItemCountRule>
     */
    public static function rules(): array
    {
        return self::RULES;
    }

    public static function annotate(string $html, string $blockId): string
    {
        $rule = self::RULES[$blockId] ?? null;

        if ($rule === null || $html === '') {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $wrapped = '<?xml encoding="UTF-8"><div id="vb-section-annotate-root">'.$html.'</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $roots = $xpath->query($rule['root']);

        if ($roots === false || $roots->length === 0) {
            return $html;
        }

        /** @var DOMElement $itemsRoot */
        $itemsRoot = $roots->item(0);
        $items = $xpath->query($rule['item'], $itemsRoot);

        if ($items === false || $items->length < 1) {
            return $html;
        }

        $count = $items->length;
        $min = (int) ($rule['min'] ?? 1);
        $max = (int) ($rule['max'] ?? 8);

        $itemsRoot->setAttribute('data-vb-items-root', '');
        $itemsRoot->setAttribute('data-vb-item-min', (string) $min);
        $itemsRoot->setAttribute('data-vb-item-max', (string) $max);

        foreach ($items as $item) {
            if ($item instanceof DOMElement) {
                $item->setAttribute('data-vb-item', '');
            }
        }

        $sections = $document->getElementsByTagName('section');

        if ($sections->length > 0) {
            /** @var DOMElement $section */
            $section = $sections->item(0);
            $section->setAttribute('data-vb-item-count', (string) $count);
            $section->setAttribute('data-vb-item-min', (string) $min);
            $section->setAttribute('data-vb-item-max', (string) $max);
        }

        $root = $document->getElementById('vb-section-annotate-root');

        if (! $root) {
            return $html;
        }

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output !== '' ? $output : $html;
    }
}
