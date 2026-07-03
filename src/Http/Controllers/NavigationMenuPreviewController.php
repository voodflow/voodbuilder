<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\LandingMenuPlacements;

final class NavigationMenuPreviewController
{
    public function __invoke(NavigationMenu $navigationMenu): View
    {
        $placement = (string) $navigationMenu->slug;

        return view('voodbuilder::admin.navigation-menu-preview', [
            'menu' => $navigationMenu,
            'placement' => $placement,
            'isHeaderPlacement' => in_array($placement, ['main', 'header_extra', 'landing_nav'], true),
            'isFooterPlacement' => $placement === 'footer'
                || $placement === 'landing_footer'
                || in_array($placement, LandingMenuPlacements::footerColumnSlugs(), true),
        ]);
    }
}
