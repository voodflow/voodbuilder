<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;

class ListNavigationMenus extends ListRecords
{
    protected static string $resource = NavigationMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
