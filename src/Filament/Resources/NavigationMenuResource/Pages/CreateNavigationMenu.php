<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources\NavigationMenuResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Vpress\Filament\Resources\NavigationMenuResource;
use Voodflow\Vpress\Models\NavigationMenu;
use Voodflow\Vpress\Support\Navigation;

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
