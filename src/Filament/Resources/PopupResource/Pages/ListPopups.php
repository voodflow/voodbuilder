<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource;

class ListPopups extends ListRecords
{
    protected static string $resource = PopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
