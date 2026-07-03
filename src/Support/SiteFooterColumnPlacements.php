<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

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
            $labels[self::columnSlug($index)] = __('voodbuilder::admin.navigation.footer_column_placement', [
                'number' => $index,
            ]);
        }

        return $labels;
    }

    public static function defaultColumnTitle(int $index): string
    {
        return match ($index) {
            1 => __('voodbuilder::pro.grapesjs.blocks.footer_col_1_title'),
            2 => __('voodbuilder::pro.grapesjs.blocks.footer_col_2_title'),
            3 => __('voodbuilder::pro.grapesjs.blocks.footer_col_3_title'),
            4 => __('voodbuilder::pro.grapesjs.blocks.footer_col_4_title'),
            default => __('Footer column :number', ['number' => $index]),
        };
    }
}
