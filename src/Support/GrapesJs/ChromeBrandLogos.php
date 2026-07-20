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

        return $out;
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
