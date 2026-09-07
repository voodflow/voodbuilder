<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Scopes chrome-layout Tailwind utilities under [data-voodbuilder-chrome-shell].
 *
 * Published pages load page JIT CSS before chrome CSS (so chrome responsive rules
 * are not overridden by page base utilities). Without scoping, chrome base utilities
 * such as .w-full then win over page responsive rules (lg:w-1/2) in the cascade.
 */
final class ChromeLayoutCssScoper
{
    public const SCOPE = '[data-voodbuilder-chrome-shell]';

    public static function scope(string $css): string
    {
        $css = trim($css);

        if ($css === '') {
            return '';
        }

        $rules = self::splitTopLevelRules($css);

        if ($rules === []) {
            return $css;
        }

        $scoped = array_map(self::scopeRule(...), $rules);

        return trim(implode("\n", array_filter($scoped, static fn (string $rule): bool => trim($rule) !== '')));
    }

    private static function scopeRule(string $rule): string
    {
        $rule = trim($rule);

        if ($rule === '') {
            return '';
        }

        if (preg_match('/^@(media|supports|container|layer)\b/i', $rule) === 1) {
            return self::scopeNestedAtRule($rule);
        }

        if (preg_match('/^@(keyframes|font-face|property|import|charset|namespace)\b/i', $rule) === 1) {
            return $rule;
        }

        $brace = strpos($rule, '{');

        if ($brace === false) {
            return $rule;
        }

        $selectors = trim(substr($rule, 0, $brace));
        $body = ltrim(substr($rule, $brace));

        return self::scopeSelectorList($selectors).' '.$body;
    }

    private static function scopeNestedAtRule(string $rule): string
    {
        $brace = strpos($rule, '{');

        if ($brace === false) {
            return $rule;
        }

        $header = substr($rule, 0, $brace + 1);
        $inner = substr($rule, $brace + 1, -1);
        $scopedInner = self::scope($inner);

        return $header.($scopedInner !== '' ? "\n".$scopedInner."\n" : '').'}';
    }

    private static function scopeSelectorList(string $selectors): string
    {
        $parts = self::splitSelectors($selectors);

        $scoped = array_map(static function (string $selector): string {
            $selector = trim($selector);

            if ($selector === '') {
                return $selector;
            }

            if (self::shouldLeaveUnscoped($selector)) {
                return $selector;
            }

            if (str_contains($selector, 'data-voodbuilder-chrome-shell')) {
                return $selector;
            }

            return self::SCOPE.' '.$selector;
        }, $parts);

        return implode(', ', array_filter($scoped, static fn (string $selector): bool => $selector !== ''));
    }

    /**
     * @return list<string>
     */
    private static function splitSelectors(string $selectors): array
    {
        $parts = [];
        $length = strlen($selectors);
        $depth = 0;
        $start = 0;

        for ($index = 0; $index < $length; $index++) {
            $char = $selectors[$index];

            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth = max(0, $depth - 1);
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = substr($selectors, $start, $index - $start);
                $start = $index + 1;
            }
        }

        $parts[] = substr($selectors, $start);

        return $parts;
    }

    private static function shouldLeaveUnscoped(string $selector): bool
    {
        $normalized = strtolower(trim($selector));

        return in_array($normalized, [':root', ':host', 'html', 'body', '*'], true);
    }

    /**
     * @return list<string>
     */
    private static function splitTopLevelRules(string $css): array
    {
        $rules = [];
        $length = strlen($css);
        $index = 0;

        while ($index < $length) {
            while ($index < $length && ctype_space($css[$index])) {
                $index++;
            }

            if ($index >= $length) {
                break;
            }

            // Preserve comments as opaque chunks when they appear between rules.
            if ($css[$index] === '/' && ($css[$index + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $index + 2);

                if ($end === false) {
                    break;
                }

                $rules[] = substr($css, $index, $end + 2 - $index);
                $index = $end + 2;

                continue;
            }

            $start = $index;
            $depth = 0;
            $foundBrace = false;

            while ($index < $length) {
                $char = $css[$index];

                if ($char === '{') {
                    $depth++;
                    $foundBrace = true;
                } elseif ($char === '}') {
                    $depth--;

                    if ($foundBrace && $depth === 0) {
                        $index++;
                        $rules[] = substr($css, $start, $index - $start);

                        break;
                    }

                    if ($depth < 0) {
                        return [];
                    }
                }

                $index++;
            }

            if (! $foundBrace) {
                break;
            }
        }

        return $rules;
    }
}
