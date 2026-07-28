<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\GlobalTextTags;

final class SiteFooterConfig
{
    public const SOCIAL_ALIGNS = ['left', 'center', 'right'];

    public const DEFAULT_SOCIAL_ALIGN = 'center';

    /** Official product slogan — default footer tagline. */
    public const DEFAULT_TAGLINE = 'A Visual CMS for Laravel & Filament';

    /** Default copyright template (resolved via GlobalTextTags). */
    public const DEFAULT_COPYRIGHT = '© {current_year} {brand_name}';

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
        $normalized['social_align'] = self::normalizeSocialAlign($config['social_align'] ?? $normalized['social_align'] ?? null);
        $normalized['tagline'] = self::normalizeText($config['tagline'] ?? $normalized['tagline'] ?? null);
        $normalized['copyright'] = self::normalizeText($config['copyright'] ?? $normalized['copyright'] ?? null);

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
            'social_align' => self::DEFAULT_SOCIAL_ALIGN,
            'tagline' => null,
            'copyright' => null,
            'logo_desktop_light' => null,
            'logo_desktop_dark' => null,
            'logo_mobile_light' => null,
            'logo_mobile_dark' => null,
            'logo_size' => ChromeBrandLogos::DEFAULT_SIZE,
            'logo_size_mobile' => ChromeBrandLogos::DEFAULT_SIZE,
            'logo_full_width' => false,
        ];
    }

    public static function resolveTagline(?string $value = null): string
    {
        $trimmed = self::normalizeText($value);

        if ($trimmed !== null) {
            return GlobalTextTags::replace($trimmed);
        }

        $translated = trim((string) __('voodbuilder::pro.grapesjs.blocks.footer_default_tagline'));
        $template = $translated !== '' ? $translated : self::DEFAULT_TAGLINE;

        return GlobalTextTags::replace($template);
    }

    public static function resolveCopyright(?string $value = null, ?string $brandName = null): string
    {
        $overrides = [];
        $brand = trim((string) ($brandName ?? ''));

        if ($brand !== '') {
            $overrides['brand_name'] = $brand;
        }

        $trimmed = self::normalizeText($value);

        if ($trimmed !== null) {
            return GlobalTextTags::replace($trimmed, $overrides);
        }

        return GlobalTextTags::replace(self::DEFAULT_COPYRIGHT, $overrides);
    }

    public static function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function normalizeSocialAlign(mixed $value): string
    {
        $align = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($align, self::SOCIAL_ALIGNS, true) ? $align : self::DEFAULT_SOCIAL_ALIGN;
    }

    public static function socialJustifyClass(mixed $align): string
    {
        return match (self::normalizeSocialAlign($align)) {
            'left' => 'justify-start',
            'right' => 'justify-end',
            default => 'justify-center',
        };
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
