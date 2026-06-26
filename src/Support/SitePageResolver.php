<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vtuts\Support\Locales;

final class SitePageResolver
{
    private static ?bool $hasLocalizationColumns = null;

    public static function hasLocalizationColumns(): bool
    {
        if (self::$hasLocalizationColumns !== null) {
            return self::$hasLocalizationColumns;
        }

        if (! Schema::hasTable('site_pages')) {
            return self::$hasLocalizationColumns = false;
        }

        return self::$hasLocalizationColumns = Schema::hasColumn('site_pages', 'locale')
            && Schema::hasColumn('site_pages', 'translation_group_id');
    }

    public static function publishedFromSlug(string $slug): SitePage
    {
        if (! self::hasLocalizationColumns()) {
            return SitePage::query()
                ->where('slug', $slug)
                ->published()
                ->firstOrFail();
        }

        $locale = self::preferredLocale();

        $exact = SitePage::query()
            ->where('slug', $slug)
            ->where('locale', $locale)
            ->published()
            ->first();

        if ($exact !== null) {
            return $exact;
        }

        $candidates = SitePage::query()
            ->where('slug', $slug)
            ->published()
            ->orderBy('id')
            ->get();

        if ($candidates->isEmpty()) {
            throw (new ModelNotFoundException)->setModel(SitePage::class, [$slug]);
        }

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $primary = class_exists(Locales::class) ? Locales::default() : $locale;

        return $candidates->firstWhere('locale', $locale)
            ?? $candidates->firstWhere('locale', $primary)
            ?? $candidates->first();
    }

    public static function publishedForMenu(string $slug): ?SitePage
    {
        if (! self::hasLocalizationColumns()) {
            return SitePage::query()
                ->published()
                ->where('slug', $slug)
                ->first();
        }

        $locale = self::preferredLocale();

        $exact = SitePage::query()
            ->published()
            ->where('slug', $slug)
            ->where('locale', $locale)
            ->first();

        if ($exact !== null) {
            return $exact;
        }

        $source = SitePage::query()
            ->published()
            ->where('slug', $slug)
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [
                class_exists(Locales::class) ? Locales::default() : $locale,
            ])
            ->first();

        if ($source === null) {
            return null;
        }

        if ($source->locale === $locale) {
            return $source;
        }

        return $source->translationFor($locale) ?? $source;
    }

    public static function localizationEnabled(): bool
    {
        return self::hasLocalizationColumns()
            && class_exists(Locales::class)
            && (bool) config('vtuts.features.localization', false)
            && count(Locales::codes()) > 1;
    }

    public static function preferredLocale(): string
    {
        if (! class_exists(Locales::class)) {
            return 'en';
        }

        $locale = app()->getLocale();

        return Locales::isValid($locale) ? $locale : Locales::default();
    }
}
