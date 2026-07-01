<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * Ensures GrapesJS form blocks use the shared vb-gjs-form styles on published pages.
 */
final class GrapesJsFormNormalizer
{
    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<form')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches): string {
                $attributes = $matches[1];

                if (str_contains($attributes, 'vb-gjs-form')) {
                    return $matches[0];
                }

                if (preg_match('/\bclass=(["\'])(.*?)\1/i', $attributes, $classMatch)) {
                    $quote = $classMatch[1];
                    $classes = trim($classMatch[2].' vb-gjs-form');
                    $attributes = (string) preg_replace(
                        '/\bclass=(["\']).*?\1/i',
                        'class='.$quote.$classes.$quote,
                        $attributes,
                        1,
                    );

                    return '<form'.$attributes.'>';
                }

                return '<form class="vb-gjs-form"'.$attributes.'>';
            },
            $html,
        );
    }
}
