<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Vtuts\Support\Locales;

/**
 * Site Page Translation.
 */
final class SitePageTranslation
{
    /**
     * @return array<string, string> locale => label
     */
    public static function availableTargetLocales(SitePage $page): array
    {
        $existing = self::existingLocales($page);

        return collect(Locales::options())
            ->reject(fn (string $label, string $code): bool => $code === $page->locale || in_array($code, $existing, true))
            ->all();
    }

    /** @return list<string> */
    public static function existingLocales(SitePage $page): array
    {
        self::ensureTranslationGroup($page);

        return SitePage::query()
            ->where('translation_group_id', $page->translation_group_id)
            ->whereKeyNot($page->getKey())
            ->pluck('locale')
            ->all();
    }

    public static function createFrom(SitePage $source, string $targetLocale): SitePage
    {
        if (! in_array($targetLocale, Locales::codes(), true)) {
            throw new \InvalidArgumentException("Unsupported locale [{$targetLocale}].");
        }

        if ($targetLocale === $source->locale) {
            throw new \InvalidArgumentException('Target locale must differ from the source page.');
        }

        self::ensureTranslationGroup($source);

        if (SitePage::query()
            ->where('translation_group_id', $source->translation_group_id)
            ->where('locale', $targetLocale)
            ->exists()) {
            throw new \InvalidArgumentException("A translation for [{$targetLocale}] already exists.");
        }

        $source->loadMissing(['seo']);

        $translation = $source->replicate([
            'published_at',
            'slug',
        ]);

        $translation->fill([
            'locale' => $targetLocale,
            'slug' => null,
            'title' => $source->title,
            'published' => false,
            'published_at' => null,
            'translation_group_id' => $source->translation_group_id,
        ]);

        $translation->save();

        self::copySeo($source, $translation);

        if ($translation->is_home) {
            SitePageHome::assignHome($translation);
        }

        return $translation->refresh();
    }

    public static function ensureTranslationGroup(SitePage $page): void
    {
        if (filled($page->translation_group_id)) {
            return;
        }

        $page->forceFill([
            'translation_group_id' => (string) Str::uuid(),
        ])->saveQuietly();
    }

    protected static function copySeo(SitePage $source, SitePage $translation): void
    {
        if (! $source->seo?->exists) {
            return;
        }

        $attributes = collect($source->seo->getAttributes())
            ->except(['id', 'model_type', 'model_id', 'created_at', 'updated_at'])
            ->all();

        $translation->seo()->update($attributes);
    }
}
