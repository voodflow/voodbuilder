<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Voodflow\Vpress\Models\NavigationMenu;
use Voodflow\Vpress\Models\NavigationMenuItem;

final class Navigation
{
    /** @return Collection<int, NavigationMenuItem> */
    public static function items(string $menuSlug): Collection
    {
        if (! Schema::hasTable('vpress_menus')) {
            return collect();
        }

        $cached = Cache::get("vpress.menu.{$menuSlug}");

        if (is_array($cached)) {
            return static::hydrateItems($cached);
        }

        $items = static::loadItems($menuSlug);

        Cache::put("vpress.menu.{$menuSlug}", static::dehydrateItems($items), 3600);

        return $items;
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

    public static function clearCache(?string $menuSlug = null): void
    {
        if ($menuSlug !== null) {
            foreach (static::slugAliases($menuSlug) as $slug) {
                Cache::forget("vpress.menu.{$slug}");
            }

            return;
        }

        if (! Schema::hasTable('vpress_menus')) {
            return;
        }

        NavigationMenu::query()->pluck('slug')->each(
            fn (string $slug) => Cache::forget("vpress.menu.{$slug}")
        );
    }

    /** @return Collection<int, NavigationMenuItem> */
    protected static function loadItems(string $menuSlug): Collection
    {
        foreach (static::slugAliases($menuSlug) as $slug) {
            $menu = NavigationMenu::query()->where('slug', $slug)->first();

            if ($menu !== null) {
                return $menu->items;
            }
        }

        return collect();
    }

    /**
     * @param  Collection<int, NavigationMenuItem>  $items
     * @return list<array<string, mixed>>
     */
    protected static function dehydrateItems(Collection $items): array
    {
        return $items->map(fn (NavigationMenuItem $item): array => [
            'label' => $item->label,
            'type' => $item->type->value,
            'link' => $item->link,
            'route_match' => $item->route_match,
            'open_in_new_tab' => $item->open_in_new_tab,
            'sort_order' => $item->sort_order,
        ])->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return Collection<int, NavigationMenuItem>
     */
    protected static function hydrateItems(array $items): Collection
    {
        return collect($items)->map(fn (array $item): NavigationMenuItem => new NavigationMenuItem($item));
    }
}
