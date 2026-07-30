<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;

/**
 * Create Navigation Menu.
 */
class CreateNavigationMenu extends CreateRecord
{
    protected static string $resource = NavigationMenuResource::class;
}
