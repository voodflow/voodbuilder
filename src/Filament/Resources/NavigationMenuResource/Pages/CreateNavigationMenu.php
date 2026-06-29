<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\Navigation;

class CreateNavigationMenu extends CreateRecord
{
    protected static string $resource = NavigationMenuResource::class;

    protected function afterCreate(): void
    {
        /** @var NavigationMenu $record */
        $record = $this->record;

        Navigation::clearCache($record->slug);
    }
}
