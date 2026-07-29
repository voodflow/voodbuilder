<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Conditions;

use DOMDocument;
use DOMElement;

final class EditorConditionsAttributeNormalizer
{
    /**
     * @var list<string>
     */
    private const CORRUPTED_JSON_ATTRIBUTES = [
        'match',
        'sets',
        'conditions',
        'key',
        'compare',
        'value',
    ];

    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-conditions')) {
            return $html;
        }

        $document = self::loadDocument($html);

        foreach (self::conditionElements($document) as $element) {
            $definition = self::parseDefinition((string) $element->getAttribute('data-voodbuilder-conditions'));

            if ($definition === null || ($definition['sets'] ?? []) === []) {
                $element->removeAttribute('data-voodbuilder-conditions');
                self::pruneCorruptedJsonAttributes($element);

                continue;
            }

            $element->setAttribute('data-voodbuilder-conditions', self::encodeDefinition($definition));
            self::pruneCorruptedJsonAttributes($element);
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    /**
     * @return array{match: string, sets: list<array<string, mixed>>}|null
     */
    public static function parseDefinition(string $raw): ?array
    {
        if (blank($raw)) {
            return null;
        }

        $candidate = $raw;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $decoded = json_decode($candidate, true);

            if (is_array($decoded)) {
                return self::normalizeDefinitionShape($decoded);
            }

            $next = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5);

            if ($next === $candidate) {
                break;
            }

            $candidate = $next;
        }

        return null;
    }

    /**
     * @param  array{match?: string, sets?: list<array<string, mixed>>}  $definition
     */
    public static function encodeDefinition(array $definition): string
    {
        $json = json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{match: string, sets: list<array<string, mixed>>}
     */
    protected static function normalizeDefinitionShape(array $decoded): array
    {
        return [
            'match' => ($decoded['match'] ?? 'any') === 'all' ? 'all' : 'any',
            'sets' => is_array($decoded['sets'] ?? null) ? $decoded['sets'] : [],
        ];
    }

    protected static function loadDocument(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    /**
     * @return list<DOMElement>
     */
    protected static function conditionElements(DOMDocument $document): array
    {
        $elements = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-conditions')) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * Editor corrupts JSON condition payloads into stray attributes.
     */
    protected static function pruneCorruptedJsonAttributes(DOMElement $element): void
    {
        foreach (self::CORRUPTED_JSON_ATTRIBUTES as $name) {
            if ($element->hasAttribute($name)) {
                $element->removeAttribute($name);
            }
        }
    }

    protected static function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
