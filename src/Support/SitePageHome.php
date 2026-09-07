<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Vtuts\Support\Locales;

/**
 * Site Page Home.
 */
final class SitePageHome
{
    /**
     * Pages that would stop being the home when $page is marked as home.
     *
     * Rule: one home per locale; all active homes must belong to the same translation group.
     *
     * @return Collection<int, SitePage>
     */
    public static function conflictingHomes(SitePage $page): Collection
    {
        $query = SitePage::query()->where('is_home', true);

        if ($page->exists) {
            $query->whereKeyNot($page->getKey());
        }

        if (blank($page->translation_group_id)) {
            return $query->where('locale', $page->locale)->get();
        }

        return $query
            ->where(function (Builder $builder) use ($page): void {
                $builder
                    ->where('locale', $page->locale)
                    ->orWhere('translation_group_id', '!=', $page->translation_group_id)
                    ->orWhereNull('translation_group_id');
            })
            ->get();
    }

    public static function assignHome(SitePage $page): int
    {
        if (! $page->is_home) {
            return 0;
        }

        $ids = self::conflictingHomes($page)->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return SitePage::query()->whereIn('id', $ids)->update(['is_home' => false]);
    }

    public static function conflictSummary(SitePage $page): string
    {
        return self::conflictingHomes($page)
            ->map(function (SitePage $conflict): string {
                $localeLabel = class_exists(Locales::class)
                    ? (Locales::options()[$conflict->locale] ?? $conflict->locale)
                    : $conflict->locale;

                return "{$conflict->title} ({$localeLabel})";
            })
            ->implode(', ');
    }
}
