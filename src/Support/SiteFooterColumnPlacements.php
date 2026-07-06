<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\NavigationMenu;

final class SiteFooterColumnPlacements
{
    public const COLUMN_COUNT = 4;

    public static function columnSlug(int $index): string
    {
        return 'footer_col_'.$index;
    }

    /** @return list<string> */
    public static function columnSlugs(): array
    {
        $slugs = [];

        for ($index = 1; $index <= self::COLUMN_COUNT; $index++) {
            $slugs[] = self::columnSlug($index);
        }

        return $slugs;
    }

    /** @return array<string, string> */
    public static function placementLabels(): array
    {
        $labels = [];

        for ($index = 1; $index <= self::COLUMN_COUNT; $index++) {
            $labels[self::columnSlug($index)] = self::genericColumnLabel($index);
        }

        return $labels;
    }

    public static function columnTitle(int $index): string
    {
        $menu = NavigationMenuResolver::forPlacement(self::columnSlug($index));

        if ($menu instanceof NavigationMenu && filled($menu->name)) {
            return (string) $menu->name;
        }

        return self::genericColumnLabel($index);
    }

    /** @return array<string, string> */
    public static function columnOptionLabels(): array
    {
        $labels = [];

        for ($index = 1; $index <= self::COLUMN_COUNT; $index++) {
            $labels[(string) $index] = self::columnTitle($index);
        }

        return $labels;
    }

    public static function defaultColumnTitle(int $index): string
    {
        return self::columnTitle($index);
    }

    public static function genericColumnLabel(int $index): string
    {
        return __('voodbuilder::admin.menu_placements.footer_column', [
            'number' => max(1, $index),
        ]);
    }
}
