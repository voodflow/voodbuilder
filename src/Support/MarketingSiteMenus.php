<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Navigation menus for the local Voodflow marketing site (slug prefix "a").
 */
final class MarketingSiteMenus
{
    public const LOCALE = 'en';

    public static function seed(): void
    {
        self::seedMainMenu();
        self::seedFooterMenus();

        Navigation::clearCache();
    }

    protected static function seedMainMenu(): void
    {
        $main = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'main', 'locale' => self::LOCALE],
            ['name' => 'Main navigation'],
        );

        NavigationMenuItem::query()->where('menu_id', $main->getKey())->delete();

        NavigationMenuItem::query()->create([
            'menu_id' => $main->getKey(),
            'label' => 'Home',
            'type' => MenuItemType::Page,
            'link' => 'a',
            'sort_order' => 0,
        ]);

        $pluginsGroup = NavigationMenuItem::query()->create([
            'menu_id' => $main->getKey(),
            'label' => 'Plugins',
            'type' => MenuItemType::Group,
            'sort_order' => 1,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $main->getKey(),
            'parent_id' => $pluginsGroup->getKey(),
            'label' => 'All plugins',
            'type' => MenuItemType::Page,
            'link' => 'a-plugins',
            'sort_order' => 0,
        ]);

        foreach (MarketingSiteContent::pluginCatalog() as $index => $plugin) {
            NavigationMenuItem::query()->create([
                'menu_id' => $main->getKey(),
                'parent_id' => $pluginsGroup->getKey(),
                'label' => $plugin['name'],
                'type' => MenuItemType::Page,
                'link' => 'a-'.$plugin['slug'],
                'sort_order' => $index + 1,
            ]);
        }

        NavigationMenuItem::query()->create([
            'menu_id' => $main->getKey(),
            'label' => 'Docs',
            'type' => MenuItemType::Page,
            'link' => 'a-docs',
            'sort_order' => 2,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $main->getKey(),
            'label' => 'Tutorials',
            'type' => MenuItemType::Page,
            'link' => 'a-tutorials',
            'sort_order' => 3,
        ]);
    }

    protected static function seedFooterMenus(): void
    {
        $footer = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'footer', 'locale' => self::LOCALE],
            ['name' => 'Footer'],
        );

        NavigationMenuItem::query()->where('menu_id', $footer->getKey())->delete();

        $footer->items()->createMany([
            [
                'label' => 'Home',
                'type' => MenuItemType::Page,
                'link' => 'a',
                'sort_order' => 0,
            ],
            [
                'label' => 'Docs',
                'type' => MenuItemType::Page,
                'link' => 'a-docs',
                'sort_order' => 1,
            ],
            [
                'label' => 'Tutorials',
                'type' => MenuItemType::Page,
                'link' => 'a-tutorials',
                'sort_order' => 2,
            ],
        ]);

        self::seedFooterColumn(1, [
            [
                'label' => 'Home',
                'type' => MenuItemType::Page,
                'link' => 'a',
                'sort_order' => 0,
            ],
            [
                'label' => 'All plugins',
                'type' => MenuItemType::Page,
                'link' => 'a-plugins',
                'sort_order' => 1,
            ],
            [
                'label' => 'Docs',
                'type' => MenuItemType::Page,
                'link' => 'a-docs',
                'sort_order' => 2,
            ],
            [
                'label' => 'Tutorials',
                'type' => MenuItemType::Page,
                'link' => 'a-tutorials',
                'sort_order' => 3,
            ],
        ]);

        self::seedFooterColumn(2, self::pluginFooterLinks());

        self::seedFooterColumn(3, []);

        self::seedFooterColumn(4, self::policyFooterLinks());
    }

    /**
     * @param  list<array{label: string, type: MenuItemType, link: string, sort_order: int}>  $items
     */
    protected static function seedFooterColumn(int $index, array $items): void
    {
        $menu = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'footer_col_'.$index, 'locale' => self::LOCALE],
            ['name' => 'Footer column '.$index],
        );

        NavigationMenuItem::query()->where('menu_id', $menu->getKey())->delete();

        if ($items === []) {
            return;
        }

        $menu->items()->createMany($items);
    }

    /**
     * @return list<array{label: string, type: MenuItemType, link: string, sort_order: int}>
     */
    protected static function pluginFooterLinks(): array
    {
        $links = [];

        foreach (MarketingSiteContent::pluginCatalog() as $index => $plugin) {
            $links[] = [
                'label' => $plugin['name'],
                'type' => MenuItemType::Page,
                'link' => 'a-'.$plugin['slug'],
                'sort_order' => $index,
            ];
        }

        return $links;
    }

    /**
     * @return list<array{label: string, type: MenuItemType, link: string, sort_order: int}>
     */
    protected static function policyFooterLinks(): array
    {
        $links = [];
        $sortOrder = 0;

        foreach ([
            'privacy-policy' => 'Privacy Policy',
            'cookie-policy' => 'Cookie Policy',
        ] as $slug => $label) {
            if (! SitePage::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $links[] = [
                'label' => $label,
                'type' => MenuItemType::Page,
                'link' => $slug,
                'sort_order' => $sortOrder,
            ];

            $sortOrder++;
        }

        return $links;
    }
}
