<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

class LandingFooterSupport
{
    /**
     * @param  array<string, mixed>  $config
     * @return array{logo_url: ?string, brand_name: ?string, lines: list<array{label: string, url: ?string, is_email: bool}>}
     */
    public static function organizerColumn(array $config): array
    {
        $logoUrl = LandingBlockMedia::publicUrl($config['logo_path'] ?? $config['logo_url'] ?? null);
        $brandName = filled($config['brand_name'] ?? null) ? (string) $config['brand_name'] : null;
        $lines = [];

        foreach (['organizer_line_1', 'organizer_line_2'] as $field) {
            if (filled($config[$field] ?? null)) {
                $lines[] = self::line((string) $config[$field]);
            }
        }

        if (filled($config['organizer_email'] ?? null)) {
            $email = (string) $config['organizer_email'];
            $lines[] = self::line($email, 'mailto:'.$email, true);
        }

        return [
            'logo_url' => $logoUrl,
            'brand_name' => $brandName,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{title: string, links: list<array{label: string, url: string, open_in_new_tab: bool}>}>
     */
    public static function menuColumns(array $config): array
    {
        $fromPlacements = LandingMenuColumnsSupport::columnsFromPlacements(
            $config,
            'landing_footer',
            LandingMenuPlacements::FOOTER_COLUMN_COUNT,
        );

        if ($fromPlacements !== []) {
            return $fromPlacements;
        }

        $fromMenu = self::menuColumnsFromNavigation(
            filled($config['menu_slug'] ?? null) ? (string) $config['menu_slug'] : 'landing_footer',
        );

        if ($fromMenu !== []) {
            return $fromMenu;
        }

        return self::menuColumnsFromRepeater($config);
    }

    /**
     * @return list<array{title: string, links: list<array{label: string, url: string, open_in_new_tab: bool}>}>
     */
    /** @var list<string> */
    public const TAILBLOCKS_VARIANTS = ['a', 'b', 'c', 'd', 'e'];

    public static function resolveVariant(array $config): string
    {
        $variant = strtolower((string) ($config['variant'] ?? 'a'));

        if ($variant === 'legacy') {
            return 'legacy';
        }

        return in_array($variant, self::TAILBLOCKS_VARIANTS, true) ? $variant : 'a';
    }

    /** @return array<string, string> */
    public static function tailblocksVariantOptions(): array
    {
        return [
            'a' => __('voodbuilder::landing.footer.variants.a'),
            'b' => __('voodbuilder::landing.footer.variants.b'),
            'c' => __('voodbuilder::landing.footer.variants.c'),
            'd' => __('voodbuilder::landing.footer.variants.d'),
            'e' => __('voodbuilder::landing.footer.variants.e'),
        ];
    }

    public static function menuColumnsFromNavigation(string $menuSlug): array
    {
        $columns = [];

        foreach (Navigation::items($menuSlug) as $item) {
            if (! $item instanceof NavigationMenuItem || $item->type !== MenuItemType::Group) {
                continue;
            }

            $links = [];

            foreach ($item->children as $child) {
                if (! $child instanceof NavigationMenuItem || ! $child->hasResolvableLink()) {
                    continue;
                }

                $links[] = [
                    'label' => (string) $child->label,
                    'url' => $child->resolveUrl(),
                    'open_in_new_tab' => (bool) $child->open_in_new_tab,
                ];
            }

            if ($links === []) {
                continue;
            }

            $columns[] = [
                'title' => (string) $item->label,
                'links' => $links,
            ];

            if (count($columns) >= 4) {
                break;
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{title: string, links: list<array{label: string, url: string, open_in_new_tab: bool}>}>
     */
    protected static function menuColumnsFromRepeater(array $config): array
    {
        $columns = [];

        foreach (($config['menu_columns'] ?? []) as $column) {
            if (! is_array($column) || blank($column['title'] ?? null)) {
                continue;
            }

            $links = [];

            foreach (($column['links'] ?? []) as $link) {
                if (! is_array($link) || blank($link['label'] ?? null) || blank($link['url'] ?? null)) {
                    continue;
                }

                $links[] = [
                    'label' => (string) $link['label'],
                    'url' => (string) $link['url'],
                    'open_in_new_tab' => (bool) ($link['open_in_new_tab'] ?? false),
                ];
            }

            $columns[] = [
                'title' => (string) $column['title'],
                'links' => $links,
            ];

            if (count($columns) >= 4) {
                break;
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{type: string, label: string, url: ?string, highlight: bool}>
     */
    public static function copyrightSegments(array $config): array
    {
        $segments = [];
        $year = filled($config['copyright_year'] ?? null)
            ? (string) $config['copyright_year']
            : (string) now()->year;

        if (filled($config['copyright_brand'] ?? null)) {
            $segments[] = [
                'type' => 'text',
                'label' => '© '.$year.' '.strtoupper((string) $config['copyright_brand']),
                'url' => null,
                'highlight' => false,
            ];
        }

        if (filled($config['copyright_claim'] ?? null)) {
            $segments[] = [
                'type' => 'text',
                'label' => strtoupper((string) $config['copyright_claim']),
                'url' => null,
                'highlight' => false,
            ];
        }

        if (filled($config['copyright_highlight'] ?? null)) {
            $segments[] = [
                'type' => 'text',
                'label' => strtoupper((string) $config['copyright_highlight']),
                'url' => null,
                'highlight' => true,
            ];
        }

        foreach (($config['copyright_links'] ?? []) as $link) {
            if (! is_array($link) || blank($link['label'] ?? null)) {
                continue;
            }

            $segments[] = [
                'type' => 'link',
                'label' => (string) $link['label'],
                'url' => filled($link['url'] ?? null) ? (string) $link['url'] : null,
                'highlight' => (bool) ($link['highlight'] ?? false),
            ];
        }

        return $segments;
    }

    /**
     * @return array{label: string, url: ?string, is_email: bool}
     */
    protected static function line(string $label, ?string $url = null, bool $isEmail = false): array
    {
        return [
            'label' => $label,
            'url' => $url,
            'is_email' => $isEmail,
        ];
    }
}
