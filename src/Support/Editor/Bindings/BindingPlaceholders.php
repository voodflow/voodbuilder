<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

/**
 * Binding Placeholders.
 */
final class BindingPlaceholders
{
    public static function imageDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
            .'<rect width="800" height="500" fill="#e2e8f0"/>'
            .'<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Dynamic image</text>'
            .'</svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    public static function text(string $sourceLabel, string $fieldLabel): string
    {
        return sprintf('[%s: %s]', $sourceLabel, $fieldLabel);
    }

    public static function isPlaceholderText(string $value): bool
    {
        $value = trim($value);

        return $value !== '' && (bool) preg_match('/^\[[^:]+:[^\]]+\]$/', $value);
    }
}
