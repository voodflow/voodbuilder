<?php

namespace Voodflow\Voodbuilder\Filament\Resources\ModelIntegrationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodbuilder\Filament\Resources\ModelIntegrationResource;

class ListModelIntegrations extends ListRecords
{
    protected static string $resource = ModelIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}
