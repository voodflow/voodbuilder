<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final class LandingNavbarSupport
{
    /** @var list<string> */
    public const VARIANTS = ['a', 'b', 'c', 'd'];

    /**
     * @param  array<string, mixed>  $config
     * @return array{logo_url: ?string, brand_name: ?string, links: list<array{label: string, url: string, open_in_new_tab: bool}>, cta_label: ?string, cta_url: ?string}
     */
    public static function viewData(array $config): array
    {
        return [
            'logo_url' => LandingBlockMedia::publicUrl($config['logo_path'] ?? $config['logo_url'] ?? null),
            'brand_name' => filled($config['brand_name'] ?? null) ? (string) $config['brand_name'] : null,
            'links' => self::navLinks($config),
            'cta_label' => filled($config['cta_label'] ?? null) ? (string) $config['cta_label'] : null,
            'cta_url' => filled($config['cta_url'] ?? null) ? (string) $config['cta_url'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{label: string, url: string, open_in_new_tab: bool}>
     */
    public static function navLinks(array $config): array
    {
        $menuSlug = filled($config['menu_slug'] ?? null)
            ? (string) $config['menu_slug']
            : LandingMenuPlacements::NAV_MENU;

        $links = LandingMenuColumnsSupport::linksFromMenu($menuSlug);

        if ($links !== []) {
            return $links;
        }

        return self::linksFromRepeater($config);
    }

    public static function resolveVariant(array $config): string
    {
        $variant = strtolower((string) ($config['variant'] ?? 'a'));

        return in_array($variant, self::VARIANTS, true) ? $variant : 'a';
    }

    /** @return array<string, string> */
    public static function variantOptions(): array
    {
        return [
            'a' => __('voodbuilder::landing.navbar.variants.a'),
            'b' => __('voodbuilder::landing.navbar.variants.b'),
            'c' => __('voodbuilder::landing.navbar.variants.c'),
            'd' => __('voodbuilder::landing.navbar.variants.d'),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{label: string, url: string, open_in_new_tab: bool}>
     */
    protected static function linksFromRepeater(array $config): array
    {
        $links = [];

        foreach (($config['nav_links'] ?? []) as $link) {
            if (! is_array($link) || blank($link['label'] ?? null) || blank($link['url'] ?? null)) {
                continue;
            }

            $links[] = [
                'label' => (string) $link['label'],
                'url' => (string) $link['url'],
                'open_in_new_tab' => (bool) ($link['open_in_new_tab'] ?? false),
            ];
        }

        return $links;
    }
}
