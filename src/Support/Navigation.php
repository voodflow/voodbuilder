<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Enums\MenuLinkDisplay;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Vtuts\Support\Locales;

final class Navigation
{
    /** @return Collection<int, NavigationMenuItem> */
    public static function items(string $menuSlug): Collection
    {
        if (! function_exists('app') || ! app()->bound('db')) {
            return collect();
        }

        if (! Schema::hasTable('voodbuilder_menus')) {
            return collect();
        }

        $cacheKey = self::cacheKey($menuSlug);

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            if (! self::menuExistsForSlug($menuSlug)) {
                Cache::forget($cacheKey);
            } else {
                return self::hydrateItems($cached);
            }
        }

        $items = self::loadItems($menuSlug);

        Cache::put($cacheKey, self::dehydrateItems($items), 3600);

        return $items;
    }

    public static function linkDisplay(string $menuSlug): MenuLinkDisplay
    {
        if (! Schema::hasTable('voodbuilder_menus')) {
            return MenuLinkDisplay::TextOnly;
        }

        $menu = NavigationMenuResolver::forPlacement($menuSlug);

        if ($menu?->link_display instanceof MenuLinkDisplay) {
            return $menu->link_display;
        }

        return $menuSlug === 'social'
            ? MenuLinkDisplay::IconOnly
            : MenuLinkDisplay::TextOnly;
    }

    /** @return list<string> */
    public static function slugAliases(string $menuSlug): array
    {
        return match ($menuSlug) {
            'header_extra' => ['header_extra', 'header-extra'],
            'header-extra' => ['header_extra', 'header-extra'],
            default => [$menuSlug],
        };
    }

    public static function clearCache(?string $menuSlug = null, ?string $locale = null): void
    {
        if ($menuSlug !== null) {
            foreach (self::slugAliases($menuSlug) as $slug) {
                if ($locale !== null) {
                    Cache::forget(self::cacheKey($slug, $locale));

                    continue;
                }

                if (NavigationMenuResolver::localizationEnabled() && class_exists(Locales::class)) {
                    foreach (Locales::codes() as $code) {
                        Cache::forget(self::cacheKey($slug, $code));
                    }

                    continue;
                }

                Cache::forget(self::cacheKey($slug));
            }

            return;
        }

        if (! Schema::hasTable('voodbuilder_menus')) {
            return;
        }

        NavigationMenu::query()
            ->get(['slug', 'locale'])
            ->each(function (NavigationMenu $menu): void {
                self::clearCache($menu->slug, $menu->locale);
            });
    }

    /** @return Collection<int, NavigationMenuItem> */
    protected static function loadItems(string $menuSlug): Collection
    {
        $menu = NavigationMenuResolver::forPlacement($menuSlug);

        if ($menu === null) {
            return collect();
        }

        return $menu->rootItems()->with('children')->get();
    }

    protected static function menuExistsForSlug(string $menuSlug): bool
    {
        return NavigationMenuResolver::forPlacement($menuSlug) !== null;
    }

    protected static function cacheKey(string $menuSlug, ?string $locale = null): string
    {
        if (NavigationMenuResolver::localizationEnabled()) {
            $locale ??= SitePageResolver::preferredLocale();

            return "voodbuilder.menu.{$menuSlug}.{$locale}";
        }

        return "voodbuilder.menu.{$menuSlug}";
    }

    /**
     * @param  Collection<int, NavigationMenuItem>  $items
     * @return list<array<string, mixed>>
     */
    protected static function dehydrateItems(Collection $items): array
    {
        return $items
            ->map(fn (NavigationMenuItem $item): array => self::dehydrateItem($item))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    protected static function dehydrateItem(NavigationMenuItem $item): array
    {
        return [
            'label' => $item->label,
            'icon' => $item->icon,
            'type' => $item->typeKey(),
            'link' => $item->link,
            'route_parameters' => $item->route_parameters,
            'route_match' => $item->route_match,
            'open_in_new_tab' => $item->open_in_new_tab,
            'sort_order' => $item->sort_order,
            'children' => self::dehydrateItems($item->children),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return Collection<int, NavigationMenuItem>
     */
    protected static function hydrateItems(array $items): Collection
    {
        return collect($items)->map(fn (array $item): NavigationMenuItem => self::hydrateItem($item));
    }

    /** @param  array<string, mixed>  $item */
    protected static function hydrateItem(array $item): NavigationMenuItem
    {
        $children = collect($item['children'] ?? [])
            ->map(fn (array $child): NavigationMenuItem => self::hydrateItem($child));

        unset($item['children']);

        $model = new NavigationMenuItem($item);
        $model->setRelation('children', $children);

        return $model;
    }
}
