<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources;

use Illuminate\Support\Arr;

/**
 * Resolves {{dot.path}} tokens against a context bag (URL templates, etc.).
 */
final class TokenResolver
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function resolve(mixed $value, array $context): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (preg_match('/^\{\{\s*([^}]+?)\s*\}\}$/', $value, $m) === 1) {
            return Arr::get($context, trim($m[1]));
        }

        return preg_replace_callback(
            '/\{\{\s*([^}]+?)\s*\}\}/',
            static function (array $matches) use ($context): string {
                $resolved = Arr::get($context, trim($matches[1]));

                if (is_scalar($resolved) || $resolved === null) {
                    return (string) ($resolved ?? '');
                }

                return json_encode($resolved, JSON_UNESCAPED_UNICODE) ?: '';
            },
            $value,
        ) ?? $value;
    }
}
