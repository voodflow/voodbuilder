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
        $html = self::highlightVpCodeBlocks($html);
        $html = self::dedupeNestedVpCodeBlocks($html);

        return $html;
    }

    protected static function highlightVpCodeBlocks(string $html): string
    {
        if (! str_contains($html, 'vp-code-block')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<div(?=[^>]*\bdata-code-block\b)(?=[^>]*\bclass="[^"]*vp-code-block[^"]*")[^>]*>[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/',
            static function (array $matches): string {
                $block = $matches[0];

                if (str_contains($block, 'class="shiki') || str_contains($block, "class='shiki")) {
                    return $block;
                }

                if (! preg_match('/<span class="vp-code-block__lang">([^<]*)<\/span>/', $block, $langMatch)
                    && ! preg_match('/<code class="language-([\w+#.-]+)"/', $block, $langMatch)) {
                    return $block;
                }

                $language = strtolower(trim($langMatch[1] ?? 'text'));

                if ($language === 'code') {
                    $language = 'text';
                }

                if (! preg_match('/<code[^>]*>([\s\S]*?)<\/code>/', $block, $codeMatch)) {
                    return $block;
                }

                $code = html_entity_decode(strip_tags($codeMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (trim($code) === '') {
                    return $block;
                }

                return MarkdownCodeBlocks::toHighlightedHtml($language, $code);
            },
            $html,
        );
    }

    protected static function dedupeNestedVpCodeBlocks(string $html): string
    {
        if (! str_contains($html, 'vp-code-block')) {
            return $html;
        }

        $previous = null;

        while ($previous !== $html) {
            $previous = $html;

            $html = (string) preg_replace(
                '/(<div class="vp-code-block__body"[^>]*>)\s*<div class="vp-code-block"[^>]*>\s*<div class="vp-code-block__header">[\s\S]*?<\/div>\s*<div class="vp-code-block__body"[^>]*>([\s\S]*?)<\/div>\s*<\/div>(\s*<\/div>)/',
                '$1$2$3',
                $html,
            );
        }

        return $html;
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
