<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\SiteVisit;

use Throwable;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

/**
 * Select options for the Site Visit trigger node.
 */
final class SiteVisitTargetOptions
{
    /**
     * @return array<string, string>
     */
    public static function matchTypes(): array
    {
        return [
            'any' => 'Any page',
            'page' => 'Site page',
            'menu_item' => 'Menu item',
            'path' => 'Custom path',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pages(): array
    {
        try {
            $query = SitePage::query()
                ->orderByDesc('is_home')
                ->orderBy('title');

            if (SitePageResolver::localizationEnabled()) {
                $query->where('locale', SitePageResolver::preferredLocale());
            }

            $options = [];

            foreach ($query->get(['id', 'title', 'slug', 'is_home']) as $page) {
                $path = SiteVisitPath::fromUrl(VoodbuilderUrls::page($page));
                $label = (string) $page->title;

                if ($page->is_home) {
                    $label .= ' (Home)';
                }

                $options[(string) $page->getKey()] = $label.' · '.$path;
            }

            return $options;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    public static function menuItems(): array
    {
        try {
            $options = [];

            $items = NavigationMenuItem::query()
                ->with('menu')
                ->orderBy('label')
                ->get();

            foreach ($items as $item) {
                if ($item->type === MenuItemType::Group) {
                    continue;
                }

                $url = $item->resolveUrl();

                if (! is_string($url) || $url === '' || $url === '#' || str_starts_with($url, 'mailto:')) {
                    continue;
                }

                $path = SiteVisitPath::fromUrl($url);

                if ($path === '') {
                    continue;
                }

                $menuName = $item->menu?->name ?? 'Menu';
                $options[(string) $item->getKey()] = $item->label.' · '.$path.' ('.$menuName.')';
            }

            return $options;
        } catch (Throwable) {
            return [];
        }
    }
}
