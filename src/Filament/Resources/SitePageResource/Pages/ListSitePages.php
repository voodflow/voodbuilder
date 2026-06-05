<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources\SitePageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Vpress\Filament\Resources\SitePageResource;

class ListSitePages extends ListRecords
{
    protected static string $resource = SitePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
