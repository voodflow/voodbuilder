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
                    $label .= ' (' . strtoupper((string) $page->locale) . ')';
                }

                if ($page->is_home) {
                    $label .= ' (' . __('Home') . ')';
                } elseif (! $page->published) {
                    $label .= ' (' . __('Draft') . ')';
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
        $items = NavigationMenuItem::query()
            ->with(['menu', 'parent'])
            ->orderBy('menu_id')
            ->orderBy('sort_order')
            ->get();

        $out = [];

        foreach ($items as $item) {
            if ($item->type !== MenuItemType::Group) {
                $out[] = [
                    'id' => (string) $item->getKey(),
                    'label' => self::menuItemLabel($item),
                    'url' => $item->resolveUrl(),
                ];
            }

            // Plugin types (e.g. vdocs "docs") expose dropdown children only at
            // render time — they are not rows in voodbuilder_menu_items. Include
            // them so button/link "Menu item" can target Docs → VoodBuilder, etc.
            foreach ($item->navigationChildren() as $child) {
                if ($child->exists) {
                    // Persisted children are already in $items.
                    continue;
                }

                $url = $child->resolveUrl();

                if ($url === '' || $url === '#') {
                    continue;
                }

                $out[] = [
                    'id' => self::dynamicMenuChildId($item, $url),
                    'label' => self::menuItemLabel($item, $child),
                    'url' => $url,
                ];
            }
        }

        return $out;
    }

    /**
     * Stable synthetic id for ephemeral menu children (not stored in DB).
     */
    public static function dynamicMenuChildId(NavigationMenuItem $parent, string $url): string
    {
        return 'dyn:' . $parent->getKey() . ':' . substr(hash('sha256', $url), 0, 16);
    }

    /**
     * Human label: "Main navigation · Docs · VoodBuilder".
     */
    public static function menuItemLabel(NavigationMenuItem $item, ?NavigationMenuItem $child = null): string
    {
        $parts = [trim((string) ($item->menu?->name ?? __('Menu')))];

        if ($child !== null) {
            $parts[] = trim((string) $item->label);
            $parts[] = trim((string) $child->label);
        } else {
            if ($item->parent) {
                $parts[] = trim((string) $item->parent->label);
            }

            $parts[] = trim((string) $item->label);
        }

        return implode(' · ', array_values(array_filter(
            $parts,
            static fn (string $part): bool => $part !== '',
        )));
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
