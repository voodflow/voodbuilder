<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\MarkdownCodeBlocks;

/**
 * Ensures GrapesJS code snippets use the shared vp-code-block shell (vdocs / vtuts).
 */
final class GrapesJsCodeBlockNormalizer
{
    public static function normalize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = self::wrapLegacyCustomCode($html);

        return MarkdownCodeBlocks::enhance($html);
    }

    protected static function wrapLegacyCustomCode(string $html): string
    {
        return (string) preg_replace_callback(
            '/<(?:div|span)[^>]*\bdata-gjs-type=(["\'])custom-code\1[^>]*>([\s\S]*?)<\/(?:div|span)>/i',
            static function (array $matches): string {
                $inner = trim(strip_tags($matches[2]));

                if ($inner === '' || str_contains($matches[0], 'data-code-block')) {
                    return $matches[0];
                }

                return MarkdownCodeBlocks::fromPlainCode(self::guessLanguage($inner), $inner);
            },
            $html,
        );
    }

    protected static function guessLanguage(string $code): string
    {
        $trimmed = ltrim($code);

        if ($trimmed === '') {
            return 'text';
        }

        if (($trimmed[0] === '{' || $trimmed[0] === '[') && json_validate($trimmed)) {
            return 'json';
        }

        if (str_starts_with($trimmed, '<?php') || str_contains($trimmed, 'namespace ') || str_contains($trimmed, 'function ')) {
            return 'php';
        }

        if (preg_match('/^\s*(import|export|const|let|function)\s/m', $trimmed)) {
            return 'javascript';
        }

        if (preg_match('/^\s*(<html|<div|<section|<svg)\b/i', $trimmed)) {
            return 'html';
        }

        return 'text';
    }
}
