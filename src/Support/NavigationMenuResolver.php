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
        if (in_array($slug, SiteFooterColumnPlacements::columnSlugs(), true)) {
            $index = (int) preg_replace('/\D+/', '', $slug);

            return __('Footer column :number', ['number' => max(1, $index)]);
        }

        return match ($slug) {
            'main' => __('Main navigation'),
            'header_extra' => __('Header extras'),
            'footer' => __('Footer links'),
            'landing_footer' => __('Landing footer columns'),
            default => $slug,
        };
    }
}
