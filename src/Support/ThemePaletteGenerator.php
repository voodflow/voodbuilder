<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final class ThemePaletteGenerator
{
    public static function seedFromThemeId(string $id): string
    {
        $hue = abs(crc32($id)) % 360;

        return self::hslToHex($hue, 0.58, 0.48);
    }

    /**
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public static function fromThemeId(string $id): array
    {
        return self::fromSeeds(self::seedFromThemeId($id));
    }

    /**
     * @return array{light: array<string, string>, dark: array<string, string>}
     */
    public static function fromSeeds(string $primary, ?string $secondary = null, ?string $headerBg = null): array
    {
        $primary = ThemePalette::sanitizeColor($primary);

        if ($primary === null) {
            throw new \InvalidArgumentException('A valid accent color is required.');
        }

        $secondary = ThemePalette::sanitizeColor($secondary) ?? self::darken($primary, 0.14);
        $headerBg = ThemePalette::sanitizeColor($headerBg) ?? '#0f172a';

        $light = [
            'primary' => $primary,
            'secondary' => $secondary,
            'header_bg' => $headerBg,
            'header_text' => self::contrastingText($headerBg),
            'body_bg' => '#ffffff',
            'text' => '#111827',
        ];

        return [
            'light' => $light,
            'dark' => self::darkFromLight($light),
        ];
    }

    /**
     * @param  array<string, ?string>  $light
     * @return array<string, string>
     */
    public static function darkFromLight(array $light): array
    {
        $primary = ThemePalette::sanitizeColor($light['primary'] ?? null) ?? '#a8b1ff';
        $secondary = ThemePalette::sanitizeColor($light['secondary'] ?? null) ?? self::lighten($primary, 0.08);
        $headerBg = ThemePalette::sanitizeColor($light['header_bg'] ?? null) ?? '#020617';
        $bodyBg = ThemePalette::sanitizeColor($light['body_bg'] ?? null) ?? '#ffffff';

        $darkHeaderBg = self::mix($headerBg, '#000000', 0.72);
        $darkBodyBg = self::isLightSurface($bodyBg)
            ? self::mix($primary, '#0f172a', 0.08)
            : self::darken($bodyBg, 0.2);

        return [
            'primary' => self::lighten($primary, 0.38),
            'secondary' => self::lighten($secondary, 0.28),
            'header_bg' => $darkHeaderBg,
            'header_text' => self::contrastingText($darkHeaderBg),
            'body_bg' => $darkBodyBg,
            'text' => '#f8fafc',
        ];
    }

    private static function contrastingText(string $background): string
    {
        return self::relativeLuminance($background) < 0.45 ? '#ffffff' : '#111827';
    }

    private static function isLightSurface(string $hex): bool
    {
        return self::relativeLuminance($hex) > 0.6;
    }

    private static function lighten(string $hex, float $amount): string
    {
        return self::mix($hex, '#ffffff', 1 - max(0, min(1, $amount)));
    }

    private static function darken(string $hex, float $amount): string
    {
        return self::mix($hex, '#000000', 1 - max(0, min(1, $amount)));
    }

    private static function mix(string $hex, string $other, float $weight): string
    {
        $weight = max(0, min(1, $weight));
        [$r1, $g1, $b1] = self::hexToRgb($hex);
        [$r2, $g2, $b2] = self::hexToRgb($other);

        return self::rgbToHex(
            (int) round($r1 * $weight + $r2 * (1 - $weight)),
            (int) round($g1 * $weight + $g2 * (1 - $weight)),
            (int) round($b1 * $weight + $b2 * (1 - $weight)),
        );
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim(ThemePalette::sanitizeColor($hex) ?? '#000000', '#');

        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function rgbToHex(int $red, int $green, int $blue): string
    {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, $red)),
            max(0, min(255, $green)),
            max(0, min(255, $blue)),
        );
    }

    private static function relativeLuminance(string $hex): float
    {
        [$red, $green, $blue] = self::hexToRgb($hex);

        $channels = array_map(static function (int $channel): float {
            $normalized = $channel / 255;

            return $normalized <= 0.03928
                ? $normalized / 12.92
                : (($normalized + 0.055) / 1.055) ** 2.4;
        }, [$red, $green, $blue]);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private static function hslToHex(float $hue, float $saturation, float $lightness): string
    {
        $hue = fmod($hue, 360);
        $saturation = max(0, min(1, $saturation));
        $lightness = max(0, min(1, $lightness));

        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $huePrime = $hue / 60;
        $x = $chroma * (1 - abs(fmod($huePrime, 2) - 1));
        $match = $lightness - $chroma / 2;

        [$red, $green, $blue] = match (true) {
            $huePrime < 1 => [$chroma, $x, 0],
            $huePrime < 2 => [$x, $chroma, 0],
            $huePrime < 3 => [0, $chroma, $x],
            $huePrime < 4 => [0, $x, $chroma],
            $huePrime < 5 => [$x, 0, $chroma],
            default => [$chroma, 0, $x],
        };

        return self::rgbToHex(
            (int) round(($red + $match) * 255),
            (int) round(($green + $match) * 255),
            (int) round(($blue + $match) * 255),
        );
    }
}
