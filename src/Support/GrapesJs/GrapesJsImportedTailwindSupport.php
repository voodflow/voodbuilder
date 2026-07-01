<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;

final class GrapesJsImportedTailwindSupport
{
    /**
     * @var array<string, string>
     */
    private const CLASS_REPLACEMENTS = [
        'bg-linear-to-t' => 'bg-gradient-to-t',
        'bg-linear-to-tr' => 'bg-gradient-to-tr',
        'bg-linear-to-r' => 'bg-gradient-to-r',
        'bg-linear-to-br' => 'bg-gradient-to-br',
        'bg-linear-to-b' => 'bg-gradient-to-b',
        'bg-linear-to-bl' => 'bg-gradient-to-bl',
        'bg-linear-to-l' => 'bg-gradient-to-l',
        'bg-linear-to-tl' => 'bg-gradient-to-tl',
        'shadow-xs' => 'shadow-sm',
    ];

    /**
     * @var array<string, string>
     */
    private const LINE_HEIGHT_REPLACEMENTS = [
        'text-sm/6' => 'text-sm leading-6',
        'text-base/7' => 'text-base leading-7',
        'text-xl/8' => 'text-xl leading-8',
        'sm:text-xl/8' => 'sm:text-xl sm:leading-8',
    ];

    public static function prepareHtml(string $html): string
    {
        $html = self::migrateClassTokens($html);
        $html = self::simplifyCustomElements($html);
        $html = self::stripNonStandardAttributes($html);
        $html = self::markPastedComponentRoot($html);

        return self::ensureDarkVariantScope($html);
    }

    public static function ensureDarkVariantScope(string $html): string
    {
        if ($html === '' || ! preg_match('/\bdark:[A-Za-z0-9_\-!\/\[\]#%.]+/', $html)) {
            return $html;
        }

        return preg_replace_callback(
            '/\bclass=(["\'])([^"\']*\bvoodbuilder-pasted-component\b[^"\']*)\1/i',
            static function (array $matches): string {
                $classes = preg_split('/\s+/', trim($matches[2])) ?: [];

                if (in_array('dark', $classes, true)) {
                    return $matches[0];
                }

                return 'class='.$matches[1].'dark '.$matches[2].$matches[1];
            },
            $html,
            1,
        ) ?? $html;
    }

    /**
     * @return list<string>
     */
    public static function extractClassNames(string $html): array
    {
        if (! preg_match_all('/\bclass=(["\'])(.*?)\1/is', $html, $matches)) {
            return [];
        }

        $classes = [];

        foreach ($matches[2] as $classAttribute) {
            foreach (preg_split('/\s+/', trim(html_entity_decode($classAttribute, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?: [] as $class) {
                $class = trim($class);

                if ($class !== '') {
                    $classes[$class] = true;
                }
            }
        }

        return array_keys($classes);
    }

    public static function migrateClassTokens(string $html): string
    {
        foreach (self::LINE_HEIGHT_REPLACEMENTS as $from => $to) {
            $html = preg_replace(
                '/\b'.preg_quote($from, '/').'\b/',
                $to,
                $html,
            ) ?? $html;
        }

        return preg_replace_callback(
            '/\bclass=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $classes = preg_split('/\s+/', trim($matches[2])) ?: [];
                $migrated = [];

                foreach ($classes as $class) {
                    $class = trim($class);

                    if ($class === '') {
                        continue;
                    }

                    $migrated[] = self::CLASS_REPLACEMENTS[$class] ?? $class;
                }

                return 'class='.$quote.implode(' ', $migrated).$quote;
            },
            $html,
        ) ?? $html;
    }

    public static function simplifyCustomElements(string $html): string
    {
        if (! str_contains($html, 'el-dialog')) {
            return $html;
        }

        $document = self::loadDocument($html);

        foreach (['el-dialog-panel', 'el-dialog'] as $tag) {
            while (true) {
                $elements = $document->getElementsByTagName($tag);

                if ($elements->length === 0) {
                    break;
                }

                $element = $elements->item(0);

                if (! $element instanceof DOMElement) {
                    break;
                }

                $replacement = $document->createElement('div');

                if ($element->hasAttributes()) {
                    foreach ($element->attributes as $attribute) {
                        if (in_array($attribute->name, ['command', 'commandfor'], true)) {
                            continue;
                        }

                        $replacement->setAttribute($attribute->name, $attribute->value);
                    }
                }

                while ($element->firstChild !== null) {
                    $replacement->appendChild($element->firstChild);
                }

                $element->parentNode?->replaceChild($replacement, $element);
            }
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    public static function stripNonStandardAttributes(string $html): string
    {
        return preg_replace('/\s(?:command|commandfor)=(["\']).*?\1/i', '', $html) ?? $html;
    }

    public static function markPastedComponentRoot(string $html): string
    {
        if ($html === '' || str_contains($html, 'voodbuilder-pasted-component')) {
            return $html;
        }

        $document = self::loadDocument($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null || $body->childNodes->length !== 1) {
            return '<div class="voodbuilder-pasted-component relative">'.$html.'</div>';
        }

        $root = $body->firstChild;

        if (! $root instanceof DOMElement) {
            return '<div class="voodbuilder-pasted-component relative">'.$html.'</div>';
        }

        $existing = trim($root->getAttribute('class'));
        $root->setAttribute('class', trim(($existing !== '' ? $existing.' ' : '').'voodbuilder-pasted-component relative'));

        return self::extractBodyHtml($document) ?? $html;
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

    protected static function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return trim($html);
    }
}
