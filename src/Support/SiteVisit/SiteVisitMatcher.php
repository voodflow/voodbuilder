<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\SiteVisit;

use Throwable;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

/**
 * Decide whether a visit payload matches a Site Visit trigger node config.
 */
final class SiteVisitMatcher
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $visit
     */
    public static function matches(array $config, array $visit): bool
    {
        $matchType = strtolower(trim((string) ($config['match_type'] ?? 'any')));
        $visitPath = SiteVisitPath::normalize((string) ($visit['path'] ?? ''));

        return match ($matchType) {
            '', 'any' => true,
            'page' => self::matchesPage($config, $visit, $visitPath),
            'menu_item' => self::matchesMenuItem($config, $visitPath),
            'path' => self::matchesCustomPath($config, $visitPath),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $visit
     */
    private static function matchesPage(array $config, array $visit, string $visitPath): bool
    {
        $pageId = trim((string) ($config['page_id'] ?? ''));

        if ($pageId === '') {
            return false;
        }

        if ((string) ($visit['page_id'] ?? '') === $pageId) {
            return true;
        }

        $expected = self::resolvePagePath($pageId);

        return $expected !== null && $expected === $visitPath;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function matchesMenuItem(array $config, string $visitPath): bool
    {
        $menuItemId = trim((string) ($config['menu_item_id'] ?? ''));

        if ($menuItemId === '') {
            return false;
        }

        $expected = self::resolveMenuItemPath($menuItemId);

        return $expected !== null && $expected === $visitPath;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function matchesCustomPath(array $config, string $visitPath): bool
    {
        $configured = trim((string) ($config['path'] ?? ''));

        if ($configured === '') {
            return false;
        }

        return SiteVisitPath::normalize($configured) === $visitPath;
    }

    private static function resolvePagePath(string $pageId): ?string
    {
        try {
            $page = SitePage::query()->find($pageId);

            if (! $page instanceof SitePage) {
                return null;
            }

            return SiteVisitPath::fromUrl(VoodbuilderUrls::page($page));
        } catch (Throwable) {
            return null;
        }
    }

    private static function resolveMenuItemPath(string $menuItemId): ?string
    {
        try {
            $item = NavigationMenuItem::query()->find($menuItemId);

            if (! $item instanceof NavigationMenuItem) {
                return null;
            }

            $url = $item->resolveUrl();

            if (! is_string($url) || $url === '' || $url === '#') {
                return null;
            }

            return SiteVisitPath::fromUrl($url);
        } catch (Throwable) {
            return null;
        }
    }
}
