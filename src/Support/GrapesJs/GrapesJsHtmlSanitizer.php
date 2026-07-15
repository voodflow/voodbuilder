<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsHtmlSanitizer
{
    /**
     * GrapesJS map/image components call decodeURIComponent on query params.
     * Section templates may ship Google Maps embeds with `width=100%`, which throws URIError.
     */
    public static function sanitize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);

        $sanitized = preg_replace_callback(
            '/\bsrc=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $src = self::encodeMalformedPercentSequences($matches[2]);

                return 'src='.$quote.$src.$quote;
            },
            $decoded,
        );

        if (! is_string($sanitized)) {
            return $html;
        }

        return self::stripInvalidAttributes($sanitized);
    }

    /**
     * Remove uncompiled Blade fragments and other invalid attribute names from HTML.
     */
    public static function stripInvalidAttributes(string $html): string
    {
        if ($html === '' || (! str_contains($html, '@') && ! str_contains($html, '(@'))) {
            return $html;
        }

        $stripped = preg_replace(
            '/\s+(?:@\w+(?:\([^)]*\))?(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?|\([^)]*\)(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?)/',
            '',
            $html,
        );

        return is_string($stripped) ? $stripped : $html;
    }

    public static function encodeMalformedPercentSequences(string $value): string
    {
        return preg_replace('/%(?![0-9A-Fa-f]{2})/', '%25', $value) ?? $value;
    }

    public static function stripEditorOnlyElements(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data-voodbuilder-top-drop-spacer')) {
            return $html;
        }

        $stripped = preg_replace(
            '/<div\b[^>]*\bdata-voodbuilder-top-drop-spacer\b[^>]*>\s*<\/div>/i',
            '',
            $html,
        );

        return is_string($stripped) ? $stripped : $html;
    }
}
