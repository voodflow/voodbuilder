<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\SitePageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodbuilder\Filament\Concerns\ScopesListTableToCanonicalTranslationGroups;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;

/**
 * List Site Pages.
 */
class ListSitePages extends ListRecords
{
    use ScopesListTableToCanonicalTranslationGroups;

    protected static string $resource = SitePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
