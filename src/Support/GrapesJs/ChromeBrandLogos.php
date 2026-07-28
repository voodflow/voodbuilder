<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Resolves desktop/mobile × light/dark logo URLs for nav/footer chrome.
 * Empty slots fall back across the matrix, then to site Appearance logos.
 */
final class ChromeBrandLogos
{
    public const KEYS = [
        'logo_desktop_light',
        'logo_desktop_dark',
        'logo_mobile_light',
        'logo_mobile_dark',
    ];

    /** @var list<string> */
    public const SIZES = ['sm', 'md', 'lg', 'xl'];

    public const DEFAULT_SIZE = 'lg';

    /**
     * Tailwind height utilities for brand logos (nav + footer).
     *
     * @var array<string, array{height: string, square: string, desktop_max: string, mobile_max: string}>
     */
    public const SIZE_MAP = [
        'sm' => [
            'height' => 'h-6',
            'square' => 'h-6 w-6',
            'desktop_max' => 'max-w-[140px]',
            'mobile_max' => 'max-w-[100px]',
        ],
        'md' => [
            'height' => 'h-8',
            'square' => 'h-8 w-8',
            'desktop_max' => 'max-w-[180px]',
            'mobile_max' => 'max-w-[120px]',
        ],
        'lg' => [
            'height' => 'h-10',
            'square' => 'h-10 w-10',
            'desktop_max' => 'max-w-[220px]',
            'mobile_max' => 'max-w-[120px]',
        ],
        'xl' => [
            'height' => 'h-12',
            'square' => 'h-12 w-12',
            'desktop_max' => 'max-w-[260px]',
            'mobile_max' => 'max-w-[160px]',
        ],
    ];

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     desktop_light: ?string,
     *     desktop_dark: ?string,
     *     mobile_light: ?string,
     *     mobile_dark: ?string,
     *     has_any: bool
     * }
     */
    public static function resolve(array $config = []): array
    {
        $raw = [];

        foreach (self::KEYS as $key) {
            $raw[$key] = self::publicUrl($config[$key] ?? null);
        }

        $globalDesktop = VoodbuilderSettings::logoUrl();
        $globalMobile = VoodbuilderSettings::logoMobileUrl();

        if ($raw['logo_desktop_light'] === null) {
            $raw['logo_desktop_light'] = $globalDesktop;
        }

        if ($raw['logo_mobile_light'] === null) {
            $raw['logo_mobile_light'] = $globalMobile ?? $globalDesktop;
        }

        $desktopLight = self::firstFilled($raw, [
            'logo_desktop_light',
            'logo_desktop_dark',
            'logo_mobile_light',
            'logo_mobile_dark',
        ]);
        $desktopDark = self::firstFilled($raw, [
            'logo_desktop_dark',
            'logo_desktop_light',
            'logo_mobile_dark',
            'logo_mobile_light',
        ]);
        $mobileLight = self::firstFilled($raw, [
            'logo_mobile_light',
            'logo_mobile_dark',
            'logo_desktop_light',
            'logo_desktop_dark',
        ]);
        $mobileDark = self::firstFilled($raw, [
            'logo_mobile_dark',
            'logo_mobile_light',
            'logo_desktop_dark',
            'logo_desktop_light',
        ]);

        return [
            'desktop_light' => $desktopLight,
            'desktop_dark' => $desktopDark,
            'mobile_light' => $mobileLight,
            'mobile_dark' => $mobileDark,
            'has_any' => $desktopLight !== null || $desktopDark !== null || $mobileLight !== null || $mobileDark !== null,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, ?string>
     */
    public static function normalizeConfigKeys(array $config): array
    {
        $out = [];

        foreach (self::KEYS as $key) {
            $value = $config[$key] ?? null;
            $out[$key] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        $out['logo_size'] = self::normalizeSize($config['logo_size'] ?? null);

        return $out;
    }

    public static function normalizeSize(mixed $size): string
    {
        $value = is_string($size) ? strtolower(trim($size)) : '';

        return in_array($value, self::SIZES, true) ? $value : self::DEFAULT_SIZE;
    }

    /**
     * @return array{height: string, square: string, desktop_max: string, mobile_max: string}
     */
    public static function sizeDefinition(mixed $size): array
    {
        $key = self::normalizeSize($size);

        return self::SIZE_MAP[$key];
    }

    public static function heightClass(mixed $size): string
    {
        return self::sizeDefinition($size)['height'];
    }

    public static function squareClass(mixed $size): string
    {
        return self::sizeDefinition($size)['square'];
    }

    public static function desktopLogoClass(mixed $size): string
    {
        $def = self::sizeDefinition($size);

        return trim($def['height'].' w-auto '.$def['desktop_max'].' object-contain object-left');
    }

    public static function mobileLogoClass(mixed $size): string
    {
        $def = self::sizeDefinition($size);

        return trim($def['height'].' w-auto '.$def['mobile_max'].' object-contain object-left');
    }

    /**
     * Footer avatar-style logo (with site name) vs wide logo-only.
     */
    public static function footerLogoClass(mixed $size, bool $logoOnly, bool $desktop = true): string
    {
        unset($desktop);
        $def = self::sizeDefinition($size);

        if ($logoOnly) {
            return trim($def['height'].' w-auto max-w-full object-contain object-left');
        }

        return trim($def['square'].' rounded-full object-cover');
    }

    public static function publicUrl(mixed $value): ?string
    {
        return VoodbuilderSettings::resolvePublicAsset($value);
    }

    /**
     * @param  array<string, ?string>  $raw
     * @param  list<string>  $order
     */
    private static function firstFilled(array $raw, array $order): ?string
    {
        foreach ($order as $key) {
            if (! empty($raw[$key])) {
                return $raw[$key];
            }
        }

        return null;
    }
}
