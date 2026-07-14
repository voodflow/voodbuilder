<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;

class ListChromeLayouts extends ListRecords
{
    protected static string $resource = ChromeLayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
