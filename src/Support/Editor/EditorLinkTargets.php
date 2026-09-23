<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MenuRouteCatalog;
use Voodflow\Voodbuilder\Support\SitePageResolver;

/**
 * Editor Link Targets.
 */
final class EditorLinkTargets
{
    /**
     * @return array{
     *     pages: list<array{id: string, label: string, url: string}>,
     *     menuItems: list<array{id: string, label: string, url: string}>,
     *     routes: list<array{id: string, label: string, url: string, requiredParams: list<string>}>
     * }
     */
    public static function catalog(): array
    {
        return [
            'pages' => self::pages(),
            'menuItems' => self::menuItems(),
            'routes' => self::routes(),
        ];
    }

    /**
     * @return list<array{id: string, label: string, url: string}>
     */
    public static function pages(): array
    {
        return SitePage::query()
            ->orderByDesc('is_home')
            ->orderBy('locale')
            ->orderBy('title')
            ->get()
            ->map(function (SitePage $page): array {
                $label = (string) $page->title;

                if (SitePageResolver::localizationEnabled()) {
                    $label .= ' ('.strtoupper((string) $page->locale).')';
                }

                if ($page->is_home) {
                    $label .= ' ('.__('Home').')';
                } elseif (! $page->published) {
                    $label .= ' ('.__('Draft').')';
                }

                return [
                    'id' => (string) $page->slug,
                    'label' => $label,
                    'url' => $page->getUrl() ?: '#',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, label: string, url: string}>
     */
    public static function menuItems(): array
    {
        return NavigationMenuItem::query()
            ->with('menu')
            ->orderBy('menu_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (NavigationMenuItem $item): bool => $item->type !== MenuItemType::Group)
            ->map(function (NavigationMenuItem $item): array {
                $menuName = $item->menu?->name ?? __('Menu');
                $label = trim($menuName.' · '.(string) $item->label);

                return [
                    'id' => (string) $item->getKey(),
                    'label' => $label,
                    'url' => $item->resolveUrl(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, label: string, url: string, requiredParams: list<string>}>
     */
    public static function routes(): array
    {
        $out = [];

        foreach (MenuRouteCatalog::options() as $name => $label) {
            $required = MenuRouteCatalog::requiredParameterNames($name);
            $url = '#';

            if ($required === []) {
                $url = self::resolveRoute($name);
            }

            $out[] = [
                'id' => $name,
                'label' => $label,
                'url' => $url,
                'requiredParams' => $required,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function resolveRoute(string $routeName, array $parameters = []): string
    {
        $name = trim($routeName);

        if ($name === '' || ! Route::has($name)) {
            return '#';
        }

        try {
            $url = route($name, array_filter(
                $parameters,
                static fn (mixed $value): bool => filled($value),
            ));

            return is_string($url) && $url !== '' ? $url : '#';
        } catch (\Throwable) {
            return '#';
        }
    }
}
