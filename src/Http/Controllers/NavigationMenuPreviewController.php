<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\NavigationMenuPreview;

final class NavigationMenuPreviewController
{
    public function __invoke(NavigationMenu $navigationMenu): View
    {
        return view('voodbuilder::admin.navigation-menu-preview', NavigationMenuPreview::context($navigationMenu));
    }
}
