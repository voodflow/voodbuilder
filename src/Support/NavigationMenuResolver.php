<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Vtuts\Support\Locales;

final class NavigationMenuResolver
{
    /**
     * @param  list<string>  $slugs
     * @return Collection<int, NavigationMenuItem>
     */
    public static function firstNonEmpty(array $slugs): Collection
    {
        foreach ($slugs as $slug) {
            $items = Navigation::items($slug);

            if ($items->isNotEmpty()) {
                return $items;
            }
        }

        return collect();
    }

    public static function localizationEnabled(): bool
    {
        return SitePageResolver::localizationEnabled()
            && Schema::hasTable('voodbuilder_menus')
            && Schema::hasColumn('voodbuilder_menus', 'locale')
            && Schema::hasColumn('voodbuilder_menus', 'translation_group_id');
    }

    public static function forPlacement(string $slug, ?string $locale = null): ?NavigationMenu
    {
        if (! Schema::hasTable('voodbuilder_menus')) {
            return null;
        }

        foreach (Navigation::slugAliases($slug) as $alias) {
            if (self::localizationEnabled()) {
                $locale ??= SitePageResolver::preferredLocale();

                $menu = NavigationMenu::query()
                    ->where('slug', $alias)
                    ->where('locale', $locale)
                    ->first();

                if ($menu !== null) {
                    return $menu;
                }

                $defaultLocale = class_exists(Locales::class) ? Locales::default() : $locale;

                if ($defaultLocale !== $locale) {
                    $menu = NavigationMenu::query()
                        ->where('slug', $alias)
                        ->where('locale', $defaultLocale)
                        ->first();

                    if ($menu !== null) {
                        return $menu;
                    }
                }

                continue;
            }

            $menu = NavigationMenu::query()->where('slug', $alias)->first();

            if ($menu !== null) {
                return $menu;
            }
        }

        return null;
    }

    public static function placementLabel(string $slug): string
    {
        if (in_array($slug, SiteFooterColumnPlacements::columnSlugs(), true)) {
            $index = (int) preg_replace('/\D+/', '', $slug);

            return __('Footer column :number', ['number' => max(1, $index)]);
        }

        return match ($slug) {
            'main' => __('Main navigation'),
            'header_extra' => __('Header extras'),
            'footer' => __('Footer links'),
            'social' => __('Social links'),
            'landing_nav' => __('Landing navbar links'),
            'landing_footer' => __('Landing footer columns'),
            default => $slug,
        };
    }
}
