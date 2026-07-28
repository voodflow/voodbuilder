<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterConfig
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalize(array $config): array
    {
        $normalized = array_merge(self::defaults(), $config);

        foreach (['show_newsletter', 'show_social', 'show_footer_menu', 'show_copyright', 'show_brand', 'show_site_name', 'show_tagline', 'footer_columns_redistribute'] as $flag) {
            if (array_key_exists($flag, $config)) {
                $normalized[$flag] = (bool) $config[$flag];
            }
        }

        $normalized = array_merge($normalized, ChromeBrandLogos::normalizeConfigKeys($config));

        self::normalizeFooterColumnFlags($normalized, $config);
        $normalized['columns'] = max(1, count(array_filter(
            [1, 2, 3, 4],
            static fn (int $index): bool => (bool) ($normalized['show_footer_col_'.$index] ?? false),
        )));

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'columns' => 4,
            'show_newsletter' => true,
            'show_social' => true,
            'show_footer_menu' => true,
            'show_copyright' => true,
            'show_brand' => true,
            'show_site_name' => true,
            'show_tagline' => true,
            'show_footer_col_1' => true,
            'show_footer_col_2' => true,
            'show_footer_col_3' => true,
            'show_footer_col_4' => true,
            'footer_columns_redistribute' => false,
            'logo_desktop_light' => null,
            'logo_desktop_dark' => null,
            'logo_mobile_light' => null,
            'logo_mobile_dark' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function isChromeVisible(array $config, string $kind): bool
    {
        $normalized = self::normalize($config);

        if (str_starts_with($kind, 'footer-col-')) {
            return self::isFooterColumnVisible($normalized, (int) substr($kind, strlen('footer-col-')));
        }

        return match ($kind) {
            'newsletter' => $normalized['show_newsletter'],
            'social' => $normalized['show_social'],
            'footer-menu' => $normalized['show_footer_menu'],
            'footer-tagline' => $normalized['show_tagline'],
            'copyright' => $normalized['show_copyright'],
            'brand' => $normalized['show_brand'] || $normalized['show_site_name'],
            'tagline' => $normalized['show_tagline'],
            'brand-column' => $normalized['show_brand']
                || $normalized['show_site_name']
                || $normalized['show_copyright']
                || $normalized['show_social']
                || $normalized['show_tagline'],
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function isFooterColumnVisible(array $config, int $index): bool
    {
        if ($index < 1 || $index > 4) {
            return false;
        }

        $normalized = self::normalize($config);

        return (bool) ($normalized['show_footer_col_'.$index] ?? true);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{class: string, attr: string}
     */
    public static function chromeAttributes(array $config, string $kind, bool $preview = false): array
    {
        $show = self::isChromeVisible($config, $kind);

        return [
            'class' => (! $preview && ! $show) ? 'hidden' : '',
            'attr' => ($preview && ! $show) ? 'data-voodbuilder-chrome-hidden' : '',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<int>
     */
    public static function visibleFooterColumnIndexes(array $config): array
    {
        $normalized = self::normalize($config);
        $indexes = [];

        for ($index = 1; $index <= 4; $index++) {
            if ($normalized['show_footer_col_'.$index] ?? false) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function columnsRedistribute(array $config): bool
    {
        return (bool) self::normalize($config)['footer_columns_redistribute'];
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $config
     */
    protected static function normalizeFooterColumnFlags(array &$normalized, array $config): void
    {
        $hasPerColumnFlags = false;

        for ($index = 1; $index <= 4; $index++) {
            if (array_key_exists('show_footer_col_'.$index, $config)) {
                $hasPerColumnFlags = true;

                break;
            }
        }

        if ($hasPerColumnFlags) {
            for ($index = 1; $index <= 4; $index++) {
                $key = 'show_footer_col_'.$index;
                $normalized[$key] = array_key_exists($key, $config)
                    ? (bool) $config[$key]
                    : (bool) ($normalized[$key] ?? true);
            }

            return;
        }

        $legacyCount = max(1, min(4, (int) ($config['columns'] ?? $normalized['columns'] ?? 4)));

        for ($index = 1; $index <= 4; $index++) {
            $normalized['show_footer_col_'.$index] = $index <= $legacyCount;
        }
    }
}
