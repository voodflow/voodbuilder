<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

final class LandingMenuColumnsSupport
{
    /**
     * @param  array<string, mixed>  $config
     * @return list<array{title: string, links: list<array{label: string, url: string, open_in_new_tab: bool}>}>
     */
    public static function columnsFromPlacements(
        array $config,
        string $prefix,
        int $count,
    ): array {
        $columns = [];

        for ($index = 1; $index <= $count; $index++) {
            $defaultSlug = "{$prefix}_col_{$index}";
            $slug = filled($config["menu_col_{$index}_slug"] ?? null)
                ? (string) $config["menu_col_{$index}_slug"]
                : $defaultSlug;

            $titleOverride = filled($config["column_{$index}_title"] ?? null)
                ? (string) $config["column_{$index}_title"]
                : null;

            $links = self::linksFromMenu($slug);

            if ($links === [] && $titleOverride === null) {
                continue;
            }

            $columns[] = [
                'title' => $titleOverride ?? self::menuTitle($slug) ?? __('voodbuilder::landing.footer.column_default', [
                    'number' => $index,
                ]),
                'links' => $links,
            ];
        }

        return $columns;
    }

    /**
     * @return list<array{label: string, url: string, open_in_new_tab: bool}>
     */
    public static function linksFromMenu(string $menuSlug): array
    {
        $links = [];

        foreach (Navigation::items($menuSlug) as $item) {
            if (! $item instanceof NavigationMenuItem) {
                continue;
            }

            if ($item->type === MenuItemType::Group) {
                foreach ($item->children as $child) {
                    if (! $child instanceof NavigationMenuItem || ! $child->hasResolvableLink()) {
                        continue;
                    }

                    $links[] = self::linkFromItem($child);
                }

                continue;
            }

            if (! $item->hasResolvableLink()) {
                continue;
            }

            $links[] = self::linkFromItem($item);
        }

        return $links;
    }

    public static function menuTitle(string $menuSlug): ?string
    {
        if (! function_exists('app') || ! app()->bound('db')) {
            return null;
        }

        $menu = NavigationMenuResolver::forPlacement($menuSlug);

        if ($menu === null) {
            return null;
        }

        $name = trim((string) $menu->name);

        return $name !== '' ? $name : null;
    }

    /**
     * @return array{label: string, url: string, open_in_new_tab: bool}
     */
    protected static function linkFromItem(NavigationMenuItem $item): array
    {
        return [
            'label' => (string) $item->label,
            'url' => $item->resolveUrl(),
            'open_in_new_tab' => (bool) $item->open_in_new_tab,
        ];
    }
}
