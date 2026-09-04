<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Resolve public page URL segments from the main navigation tree (max 2 levels).
 *
 * Nested page items under a Group / Page parent become `{parent}/{slug}`.
 * Top-level page items stay `{slug}`. Legacy flat URLs still resolve.
 */
final class SitePageMenuPath
{
    public static function enabled(): bool
    {
        return (bool) config('voodbuilder.pages.menu_paths', false);
    }

    /**
     * Relative path without leading slash (e.g. "products/a-voodflow" or "about").
     */
    public static function relativePath(SitePage $page): string
    {
        $slug = trim((string) $page->slug, '/');

        if ($slug === '' || ! self::enabled()) {
            return $slug;
        }

        $item = NavigationMenuItem::query()
            ->where('type', MenuItemType::Page)
            ->where('link', $slug)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('id')
            ->first();

        if ($item?->parent === null) {
            return $slug;
        }

        $parentSegment = self::parentSegment($item->parent);

        if ($parentSegment === null || $parentSegment === '' || $parentSegment === $slug) {
            return $slug;
        }

        return $parentSegment.'/'.$slug;
    }

    /**
     * Absolute public URL for the page (respects route_prefix + menu nesting).
     */
    public static function url(SitePage $page): string
    {
        $path = self::relativePath($page);

        if ($path === '') {
            return url('/');
        }

        if (str_contains($path, '/') && Route::has('voodbuilder.pages.show.nested')) {
            [$section, $slug] = explode('/', $path, 2);

            return route('voodbuilder.pages.show.nested', [
                'section' => $section,
                'slug' => $slug,
            ]);
        }

        if (Route::has('voodbuilder.pages.show')) {
            return route('voodbuilder.pages.show', ['slug' => $path]);
        }

        $prefix = trim((string) config('voodbuilder.pages.route_prefix', 'pages'), '/');

        return url(($prefix !== '' ? '/'.$prefix.'/' : '/').$path);
    }

    /**
     * Resolve a published page from a route slug or nested "section/slug" path.
     */
    public static function resolveSlugFromRoute(?string $section, string $slug): string
    {
        $slug = trim($slug, '/');

        if (! filled($section) || ! self::enabled()) {
            return $slug;
        }

        $section = trim($section, '/');

        $nested = NavigationMenuItem::query()
            ->where('type', MenuItemType::Page)
            ->where('link', $slug)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get()
            ->first(fn (NavigationMenuItem $item): bool => self::parentSegment($item->parent) === $section);

        return $nested?->link ? (string) $nested->link : $slug;
    }

    protected static function parentSegment(?NavigationMenuItem $parent): ?string
    {
        if ($parent === null) {
            return null;
        }

        if ($parent->type === MenuItemType::Page && filled($parent->link)) {
            return Str::slug((string) $parent->link);
        }

        if ($parent->type === MenuItemType::Group || filled($parent->label)) {
            return Str::slug((string) $parent->label);
        }

        if ($parent->type === MenuItemType::Url && filled($parent->link)) {
            $path = parse_url((string) $parent->link, PHP_URL_PATH);

            if (is_string($path) && $path !== '' && $path !== '/') {
                return Str::slug(basename($path));
            }
        }

        return filled($parent->label) ? Str::slug((string) $parent->label) : null;
    }
}
