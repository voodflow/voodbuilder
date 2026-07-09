<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Popups;

final class GrapesJsPopupHtmlNormalizer
{
    public static function normalize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        if (! str_contains(strtolower($html), '<body')) {
            return $html;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof \DOMElement) {
            return $html;
        }

        $inner = '';

        foreach ($body->childNodes as $child) {
            $inner .= $document->saveHTML($child);
        }

        return trim($inner);
    }
}
