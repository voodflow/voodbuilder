<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMNode;

final class GrapesJsDynamicBlockAttributeNormalizer
{
    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-vpress-block')) {
            return $html;
        }

        $document = self::loadDocument($html);

        foreach (self::dynamicNodes($document) as $node) {
            $blockId = trim((string) $node->getAttribute('data-vpress-block'));

            if ($blockId === '') {
                continue;
            }

            $config = self::decodeConfig((string) $node->getAttribute('data-vpress-config'));

            if ($config === []) {
                $config = self::defaultConfigFor($blockId);
            }

            $node->setAttribute('data-vpress-config', self::encodeConfig($config));
            self::pruneForeignAttributes($node);
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeConfig(string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        $candidate = $raw;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $decoded = json_decode($candidate, true);

            if (is_array($decoded)) {
                return $decoded;
            }

            $next = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5);

            if ($next === $candidate) {
                break;
            }

            $candidate = $next;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function encodeConfig(array $config): string
    {
        return json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function defaultConfigFor(string $blockId): array
    {
        $serverBlockClass = app(GrapesJsServerBlockRegistry::class)->resolve($blockId);

        if ($serverBlockClass !== null) {
            return $serverBlockClass::defaultConfig();
        }

        return GrapesJsDefaultBlockConfig::defaults()[$blockId] ?? [];
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
    protected static function dynamicNodes(DOMDocument $document): array
    {
        $nodes = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('data-vpress-block')) {
                $nodes[] = $element;
            }
        }

        return $nodes;
    }

    /**
     * GrapesJS corrupts JSON config into stray attributes (e.g. limit="", menu="").
     */
    protected static function pruneForeignAttributes(DOMElement $node): void
    {
        $allowed = ['data-vpress-block', 'data-vpress-config', 'class', 'id'];
        $remove = [];

        foreach ($node->attributes ?? [] as $attribute) {
            if (! in_array($attribute->name, $allowed, true)) {
                $remove[] = $attribute->name;
            }
        }

        foreach ($remove as $name) {
            $node->removeAttribute($name);
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
