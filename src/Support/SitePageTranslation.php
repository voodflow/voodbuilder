<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Models\SitePage;

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

        return collect(SiteLocales::options())
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
        if (! in_array($targetLocale, SiteLocales::codes(), true)) {
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

    /**
     * Locales that already have a sibling translation (regenerate candidates).
     *
     * @return array<string, string> locale => label
     */
    public static function existingTargetLocales(SitePage $page): array
    {
        $existing = self::existingLocales($page);
        $options = SiteLocales::options();

        return collect($options)
            ->filter(fn (string $label, string $code): bool => $code !== $page->locale && in_array($code, $existing, true))
            ->all();
    }

    /**
     * Copy this page's authored content onto an existing sibling translation.
     *
     * The whole point is rebuilding a translation from the source language after the
     * source was edited: the sibling keeps its own identity (slug, locale, home flag,
     * publish state, translation group) and only receives the content/design fields,
     * so URLs, menus and redirects are untouched.
     */
    public static function cloneContentInto(SitePage $source, SitePage $target, bool $copyTitle = true): void
    {
        if ($source->getKey() === $target->getKey()) {
            throw new \InvalidArgumentException('Source and target pages must differ.');
        }

        $source->loadMissing(['seo']);

        $content = [
            'content' => $source->content,
            'builder' => $source->builder,
            'builder_payload' => $source->builder_payload,
            'layout' => $source->layout,
            'hide_site_footer' => (bool) $source->hide_site_footer,
            'hide_site_nav' => (bool) $source->hide_site_nav,
            'sub_theme' => $source->sub_theme,
            'excerpt' => $source->excerpt,
        ];

        // Dynamic templates own extra routing metadata; only carry it when the source
        // actually is one, so a static page cannot blank a sibling's columns.
        if ($source->is_dynamic) {
            $content['is_dynamic'] = true;
            $content['dynamic_channel'] = $source->dynamic_channel;
            $content['dynamic_routes'] = $source->dynamic_routes;
            $content['dynamic_priority'] = $source->dynamic_priority;
        }

        if ($copyTitle) {
            $content['title'] = $source->title;
        }

        $target->fill($content)->save();

        self::copySeo($source, $target);
    }

    public static function findTarget(SitePage $source, string $locale): ?SitePage
    {
        if (blank($source->translation_group_id)) {
            return null;
        }

        return SitePage::query()
            ->where('translation_group_id', $source->translation_group_id)
            ->where('locale', $locale)
            ->whereKeyNot($source->getKey())
            ->first();
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
