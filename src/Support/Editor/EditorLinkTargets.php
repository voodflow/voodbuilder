<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageResolver;

final class EditorLinkTargets
{
    /**
     * @return array{
     *     pages: list<array{id: string, label: string, url: string}>,
     *     menuItems: list<array{id: string, label: string, url: string}>
     * }
     */
    public static function catalog(): array
    {
        return [
            'pages' => self::pages(),
            'menuItems' => self::menuItems(),
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
}
