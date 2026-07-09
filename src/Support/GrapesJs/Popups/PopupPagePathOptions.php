<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Popups;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MenuRouteCatalog;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

final class PopupPagePathOptions
{
    /**
     * @return list<array{value: string, label: string, group: string}>
     */
    public static function all(): array
    {
        $options = [[
            'value' => '',
            'label' => __('voodbuilder::popups.page_paths.all_pages'),
            'group' => __('voodbuilder::popups.page_paths.group_general'),
        ]];

        foreach (self::sitePageOptions() as $option) {
            $options[] = $option;
        }

        foreach (self::menuItemOptions() as $option) {
            $options[] = $option;
        }

        foreach (self::routeOptions() as $option) {
            $options[] = $option;
        }

        return self::dedupe($options);
    }

    /**
     * @return list<array{value: string, label: string, group: string}>
     */
    private static function sitePageOptions(): array
    {
        $group = __('voodbuilder::popups.page_paths.group_pages');
        $query = SitePage::query()
            ->orderByDesc('is_home')
            ->orderBy('title');

        if (SitePageResolver::localizationEnabled()) {
            $query->where('locale', SitePageResolver::preferredLocale());
        }

        $options = [];

        foreach ($query->get() as $page) {
            if (! $page instanceof SitePage) {
                continue;
            }

            $path = self::normalizePath(self::pathFromUrl(VoodbuilderUrls::page($page)));

            if ($path === '') {
                continue;
            }

            $label = $page->title;

            if ($page->is_home) {
                $label .= ' ('.__('Home').')';
            }

            $options[] = [
                'value' => $path,
                'label' => $label.' · '.$path,
                'group' => $group,
            ];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, label: string, group: string}>
     */
    private static function menuItemOptions(): array
    {
        $group = __('voodbuilder::popups.page_paths.group_menus');
        $options = [];

        $items = NavigationMenuItem::query()
            ->with('menu')
            ->orderBy('label')
            ->get();

        foreach ($items as $item) {
            if (! $item instanceof NavigationMenuItem || $item->type === MenuItemType::Group) {
                continue;
            }

            $url = $item->resolveUrl();

            if (! is_string($url) || $url === '' || $url === '#') {
                continue;
            }

            if (str_starts_with($url, 'mailto:')) {
                continue;
            }

            $path = self::normalizePath(self::pathFromUrl($url));

            if ($path === '') {
                continue;
            }

            $menuName = $item->menu?->name ?? __('Menu');
            $options[] = [
                'value' => $path,
                'label' => $item->label.' · '.$path,
                'group' => $group.' · '.$menuName,
            ];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, label: string, group: string}>
     */
    private static function routeOptions(): array
    {
        $group = __('voodbuilder::popups.page_paths.group_routes');
        $options = [];

        foreach (MenuRouteCatalog::options() as $routeName => $routeLabel) {
            $route = RouteFacade::getRoutes()->getByName($routeName);

            if (! $route instanceof Route) {
                continue;
            }

            if (MenuRouteCatalog::requiredParameterNames($routeName) !== []) {
                continue;
            }

            $path = self::normalizePath('/'.ltrim($route->uri(), '/'));

            if ($path === '' || str_contains($path, '{')) {
                continue;
            }

            $options[] = [
                'value' => $path,
                'label' => $routeLabel,
                'group' => $group,
            ];
        }

        return $options;
    }

    /**
     * @param  list<array{value: string, label: string, group: string}>  $options
     * @return list<array{value: string, label: string, group: string}>
     */
    private static function dedupe(array $options): array
    {
        $seen = [];
        $deduped = [];

        foreach ($options as $option) {
            $key = $option['value'].'|'.$option['group'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $option;
        }

        return $deduped;
    }

    private static function pathFromUrl(string $url): string
    {
        if (str_starts_with($url, '/')) {
            return parse_url($url, PHP_URL_PATH) ?: $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }

    private static function normalizePath(string $path): string
    {
        $normalized = '/'.trim($path, '/');

        if ($normalized === '/') {
            return '/';
        }

        return rtrim($normalized, '/');
    }
}
