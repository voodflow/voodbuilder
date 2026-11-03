<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

/**
 * Navigation Menu Resolver.
 */
final class NavigationMenuResolver
{
    private static ?bool $localizationEnabled = null;

    private static ?bool $menusTableExists = null;

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
        if (self::$localizationEnabled !== null) {
            return self::$localizationEnabled;
        }

        return self::$localizationEnabled = SitePageResolver::localizationEnabled()
            && self::menusTableExists()
            && Schema::hasColumn('voodbuilder_menus', 'locale')
            && Schema::hasColumn('voodbuilder_menus', 'translation_group_id');
    }

    public static function forPlacement(string $slug, ?string $locale = null): ?NavigationMenu
    {
        if (! self::menusTableExists()) {
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

                $defaultLocale = SiteLocales::default();

                if ($defaultLocale !== $locale) {
                    $menu = NavigationMenu::query()
                        ->where('slug', $alias)
                        ->where('locale', $defaultLocale)
                        ->first();

                    if ($menu !== null) {
                        return $menu;
                    }
                }

                /*
                 * Menus stored with a locale that has no translation yet (or before the
                 * site switched languages) must not make the navigation disappear: fall
                 * back to the placement itself, preferring the site default. The author
                 * can then create the missing translation from the menu list.
                 */
                $menu = NavigationMenu::query()
                    ->where('slug', $alias)
                    ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$defaultLocale])
                    ->orderBy('id')
                    ->first();

                if ($menu !== null) {
                    return $menu;
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

    public static function clearSchemaCache(): void
    {
        self::$localizationEnabled = null;
        self::$menusTableExists = null;
    }

    public static function menusTableExists(): bool
    {
        if (self::$menusTableExists !== null) {
            return self::$menusTableExists;
        }

        return self::$menusTableExists = Schema::hasTable('voodbuilder_menus');
    }
}
