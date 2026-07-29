<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use DOMDocument;
use DOMElement;

final class EditorDynamicBlockAttributeNormalizer
{
    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-block')) {
            return $html;
        }

        $html = self::repairCorruptedDynamicBlockMarkup($html);

        $document = self::loadDocument($html);

        foreach (self::dynamicNodes($document) as $node) {
            $blockId = trim((string) $node->getAttribute('data-voodbuilder-block'));

            if ($blockId === '') {
                continue;
            }

            $config = self::decodeConfig((string) $node->getAttribute('data-voodbuilder-config'));

            if ($config === []) {
                $config = self::salvageCorruptedConfig($node);
            }

            if ($config === []) {
                $config = self::defaultConfigFor($blockId);
            }

            $node->setAttribute('data-voodbuilder-config', self::encodeConfig($config));
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
        $serverBlockClass = app(EditorServerBlockRegistry::class)->resolve($blockId);

        if ($serverBlockClass !== null) {
            return $serverBlockClass::defaultConfig();
        }

        return EditorDefaultBlockConfig::defaults()[$blockId] ?? [];
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
            if ($element instanceof DOMElement && $element->hasAttribute('data-voodbuilder-block')) {
                $nodes[] = $element;
            }
        }

        return $nodes;
    }

    /**
     * Editor can split JSON config into stray attributes (menu="", limit="", …).
     *
     * @return array<string, mixed>
     */
    protected static function salvageCorruptedConfig(DOMElement $node): array
    {
        $allowed = ['data-voodbuilder-block', 'data-voodbuilder-config', 'class', 'id'];
        $config = [];

        foreach ($node->attributes ?? [] as $attribute) {
            if (in_array($attribute->name, $allowed, true)) {
                continue;
            }

            $value = html_entity_decode($attribute->value, ENT_QUOTES | ENT_HTML5);

            if ($value === '' && is_numeric($attribute->name)) {
                $config[$attribute->name] = (int) $attribute->name;

                continue;
            }

            if ($value === '') {
                continue;
            }

            if ($value === 'true') {
                $config[$attribute->name] = true;

                continue;
            }

            if ($value === 'false') {
                $config[$attribute->name] = false;

                continue;
            }

            if (is_numeric($value)) {
                $config[$attribute->name] = str_contains($value, '.') ? (float) $value : (int) $value;

                continue;
            }

            $config[$attribute->name] = $value;
        }

        return $config;
    }

    protected static function repairCorruptedDynamicBlockMarkup(string $html): string
    {
        $repaired = preg_replace(
            '/data-voodbuilder-config="\{"\s+menu":"([^"]+)"\}"=""/',
            'data-voodbuilder-config=\'{"menu":"$1"}\'',
            $html,
        );

        if (is_string($repaired)) {
            $html = $repaired;
        }

        $repaired = preg_replace(
            '/data-voodbuilder-config="\{"\s+class="/',
            'data-voodbuilder-config="{}" class="',
            $html,
        );

        return is_string($repaired) ? $repaired : $html;
    }

    /**
     * Editor corrupts JSON config into stray attributes (e.g. limit="", menu="").
     */
    protected static function pruneForeignAttributes(DOMElement $node): void
    {
        $allowed = ['data-voodbuilder-block', 'data-voodbuilder-config', 'class', 'id'];
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
