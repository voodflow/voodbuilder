<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\NavigationMenu;

final class NavigationMenuPreview
{
    /**
     * @return array{
     *     menu: NavigationMenu,
     *     placement: string,
     *     isHeaderPlacement: bool,
     *     isFooterPlacement: bool
     * }
     */
    public static function context(NavigationMenu $menu): array
    {
        $placement = (string) $menu->slug;

        return [
            'menu' => $menu,
            'placement' => $placement,
            'isHeaderPlacement' => in_array($placement, ['main', 'header_extra', 'landing_nav'], true),
            'isFooterPlacement' => $placement === 'footer'
                || $placement === 'landing_footer'
                || in_array($placement, LandingMenuPlacements::footerColumnSlugs(), true),
        ];
    }

    public static function renderContent(NavigationMenu $menu): string
    {
        return view(
            'voodbuilder::admin.partials.navigation-menu-preview-content',
            self::context($menu),
        )->render();
    }
}
