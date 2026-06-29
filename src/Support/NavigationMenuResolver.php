<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

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

    public static function placementLabel(string $slug): string
    {
        return match ($slug) {
            'main' => __('Main navigation'),
            'header_extra' => __('Header extras'),
            'footer' => __('Footer links'),
            'footer_col_1' => __('Footer column 1'),
            'footer_col_2' => __('Footer column 2'),
            'footer_col_3' => __('Footer column 3'),
            'footer_col_4' => __('Footer column 4'),
            'landing_footer' => __('Landing footer columns'),
            default => $slug,
        };
    }
}
