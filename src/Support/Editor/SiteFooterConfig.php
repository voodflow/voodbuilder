<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Support\GlobalTextTags;

/**
 * Site Footer Config.
 */
final class SiteFooterConfig
{
    public const SOCIAL_ALIGNS = ['left', 'center', 'right'];

    public const DEFAULT_SOCIAL_ALIGN = 'center';

    /** Official product slogan — default footer tagline. */
    public const DEFAULT_TAGLINE = 'A Visual CMS for Laravel & Filament';

    /** Max editable lines per static footer column (contact / details). */
    public const MAX_TEXT_LINES = 5;

    /** Max repeatable contact rows (icon + text) in the contacts column. */
    public const MAX_CONTACT_ITEMS = 8;

    /** Icon name used when a contact row has no icon of its own. */
    public const DEFAULT_CONTACT_ITEM_ICON = 'point';

    /** Static (non-menu) footer columns used by the contact-columns variant. */
    public const TEXT_COLUMNS = ['contact', 'details'];

    /** Default copyright template (resolved via GlobalTextTags). */
    public const DEFAULT_COPYRIGHT = '© {current_year} {brand_name}';

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalize(array $config): array
    {
        $provided = $config;
        $normalized = array_merge(self::defaults(), $config);

        foreach (['show_newsletter', 'show_social', 'show_footer_menu', 'show_copyright', 'show_brand', 'show_site_name', 'show_tagline', 'footer_columns_redistribute', 'show_logo_desktop', 'show_logo_mobile', 'show_site_name_desktop', 'show_site_name_mobile', 'vertical_dividers'] as $flag) {
            if (array_key_exists($flag, $provided)) {
                $normalized[$flag] = (bool) $provided[$flag];
            }
        }

        // Prefer explicit breakpoint flags; otherwise mirror legacy master flags.
        // Only inspect $provided — defaults must not look like user input.
        if (array_key_exists('show_logo_desktop', $provided) || array_key_exists('show_logo_mobile', $provided)) {
            $normalized['show_brand'] = $normalized['show_logo_desktop'] || $normalized['show_logo_mobile'];
        } elseif (array_key_exists('show_brand', $provided)) {
            $normalized['show_logo_desktop'] = $normalized['show_brand'];
            $normalized['show_logo_mobile'] = $normalized['show_brand'];
        }

        if (array_key_exists('show_site_name_desktop', $provided) || array_key_exists('show_site_name_mobile', $provided)) {
            $normalized['show_site_name'] = $normalized['show_site_name_desktop'] || $normalized['show_site_name_mobile'];
        } elseif (array_key_exists('show_site_name', $provided)) {
            $normalized['show_site_name_desktop'] = $normalized['show_site_name'];
            $normalized['show_site_name_mobile'] = $normalized['show_site_name'];
        }

        $normalized = array_merge($normalized, ChromeBrandLogos::normalizeConfigKeys($config));
        $normalized['social_align'] = self::normalizeSocialAlign($config['social_align'] ?? $normalized['social_align'] ?? null);
        $normalized['tagline'] = self::normalizeText($config['tagline'] ?? $normalized['tagline'] ?? null);
        $normalized['copyright'] = self::normalizeText($config['copyright'] ?? $normalized['copyright'] ?? null);

        foreach (self::TEXT_COLUMNS as $column) {
            $normalized[$column . '_title'] = self::normalizeText(
                $config[$column . '_title'] ?? $normalized[$column . '_title'] ?? null,
            );
            $normalized[$column . '_lines'] = self::normalizeLines(
                $config[$column . '_lines'] ?? $normalized[$column . '_lines'] ?? null,
            );
        }

        $normalized['contact_items'] = self::normalizeContactItems(
            $config['contact_items'] ?? $normalized['contact_items'] ?? null,
        );

        self::normalizeFooterColumnFlags($normalized, $config);
        $normalized['columns'] = max(1, count(array_filter(
            [1, 2, 3, 4],
            static fn (int $index): bool => (bool) ($normalized['show_footer_col_' . $index] ?? false),
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
            'show_logo_desktop' => true,
            'show_logo_mobile' => true,
            'show_site_name_desktop' => true,
            'show_site_name_mobile' => true,
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
            'logo_shape' => ChromeBrandLogos::DEFAULT_SHAPE,
            'contact_title' => null,
            'contact_lines' => [],
            'contact_items' => [],
            'details_title' => null,
            'details_lines' => [],
            'vertical_dividers' => false,
        ];
    }

    /**
     * Static column lines accept both an array (stored config) and a newline
     * separated string (admin textarea), trimmed and capped.
     *
     * @return list<string>
     */
    public static function normalizeLines(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $lines = [];

        foreach ($value as $line) {
            $text = self::normalizeText($line);

            if ($text !== null) {
                $lines[] = $text;
            }
        }

        return array_slice($lines, 0, self::MAX_TEXT_LINES);
    }

    /**
     * Link a static column line when it is clearly a mail, phone or URL.
     * Plain address lines stay text.
     */
    public static function lineHref(string $line): ?string
    {
        $trimmed = trim($line);

        if ($trimmed === '') {
            return null;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            return 'mailto:' . $trimmed;
        }

        if (preg_match('/^[+\d\s().\/-]{6,}$/', $trimmed) === 1) {
            return 'tel:' . preg_replace('/[^\d+]/', '', $trimmed);
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{title: string, lines: list<string>}
     */
    public static function textColumn(array $config, string $column): array
    {
        $normalized = self::normalize($config);

        return [
            'title' => (string) ($normalized[$column . '_title'] ?? ''),
            'lines' => is_array($normalized[$column . '_lines'] ?? null) ? $normalized[$column . '_lines'] : [],
        ];
    }

    /**
     * Repeatable contacts column rows: `{ icon, text }` pairs.
     *
     * Accepts the stored array, a JSON string (from the editor prop) or a plain
     * list of strings (legacy lines promoted to rows without an icon).
     *
     * @return list<array{icon: string, text: string}>
     */
    public static function normalizeContactItems(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\r\n|\r|\n/', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $row) {
            $hasIcon = false;

            if (is_string($row)) {
                $text = self::normalizeText($row);
                $icon = self::DEFAULT_CONTACT_ITEM_ICON;
            } elseif (is_array($row)) {
                $text = self::normalizeText(
                    $row['text'] ?? $row['label'] ?? $row['line'] ?? $row['value'] ?? null,
                );
                // An explicit empty icon hides the icon but keeps the text aligned.
                $hasIcon = array_key_exists('icon', $row);
                $icon = $hasIcon
                    ? (self::normalizeText($row['icon']) ?? '')
                    : self::DEFAULT_CONTACT_ITEM_ICON;
            } else {
                continue;
            }

            if ($text === null) {
                continue;
            }

            $items[] = [
                'icon' => strtolower($icon),
                'text' => $text,
            ];

            if (count($items) >= self::MAX_CONTACT_ITEMS) {
                break;
            }
        }

        return $items;
    }

    /**
     * Contacts column rows ready for rendering (link auto-detected from the text).
     *
     * @param  array<string, mixed>  $config
     * @return list<array{icon: string, text: string, href: string|null}>
     */
    public static function contactItems(array $config): array
    {
        $normalized = self::normalize($config);
        $items = is_array($normalized['contact_items'] ?? null) ? $normalized['contact_items'] : [];

        return array_map(
            static fn (array $item): array => [
                'icon' => (string) ($item['icon'] ?? self::DEFAULT_CONTACT_ITEM_ICON),
                'text' => (string) ($item['text'] ?? ''),
                'href' => self::lineHref((string) ($item['text'] ?? '')),
            ],
            $items,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function verticalDividers(array $config): bool
    {
        return (bool) self::normalize($config)['vertical_dividers'];
    }

    public static function resolveTagline(?string $value = null): string
    {
        $trimmed = self::normalizeText($value);

        if ($trimmed !== null) {
            return GlobalTextTags::replace($trimmed);
        }

        $translated = trim((string) __('voodbuilder::pro.editor.blocks.footer_default_tagline'));
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

        return (bool) ($normalized['show_footer_col_' . $index] ?? true);
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
            if ($normalized['show_footer_col_' . $index] ?? false) {
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
            if (array_key_exists('show_footer_col_' . $index, $config)) {
                $hasPerColumnFlags = true;

                break;
            }
        }

        if ($hasPerColumnFlags) {
            for ($index = 1; $index <= 4; $index++) {
                $key = 'show_footer_col_' . $index;
                $normalized[$key] = array_key_exists($key, $config)
                    ? (bool) $config[$key]
                    : (bool) ($normalized[$key] ?? true);
            }

            return;
        }

        $legacyCount = max(1, min(4, (int) ($config['columns'] ?? $normalized['columns'] ?? 4)));

        for ($index = 1; $index <= 4; $index++) {
            $normalized['show_footer_col_' . $index] = $index <= $legacyCount;
        }
    }
}
