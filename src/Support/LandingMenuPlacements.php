<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Landing Menu Placements.
 */
final class LandingMenuPlacements
{
    public const FOOTER_COLUMN_COUNT = 4;

    public const NAV_MENU = 'landing_nav';

    public static function footerColumnSlug(int $index): string
    {
        return 'landing_footer_col_'.$index;
    }

    /** @return list<string> */
    public static function footerColumnSlugs(): array
    {
        $slugs = [];

        for ($index = 1; $index <= self::FOOTER_COLUMN_COUNT; $index++) {
            $slugs[] = self::footerColumnSlug($index);
        }

        return $slugs;
    }

    /** @return array<string, string> */
    public static function footerColumnPlacementLabels(): array
    {
        $labels = [];

        for ($index = 1; $index <= self::FOOTER_COLUMN_COUNT; $index++) {
            $labels[self::footerColumnSlug($index)] = __('voodbuilder::landing.footer.column_placement', [
                'number' => $index,
            ]);
        }

        return $labels;
    }
}
