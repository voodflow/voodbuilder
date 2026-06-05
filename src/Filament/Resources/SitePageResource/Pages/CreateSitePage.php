<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources\SitePageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Vpress\Filament\Resources\SitePageResource;
use Voodflow\Vpress\Models\SitePage;

class CreateSitePage extends CreateRecord
{
    protected static string $resource = SitePageResource::class;

    protected function afterCreate(): void
    {
        /** @var SitePage $record */
        $record = $this->record;

        if ($record->is_home) {
            SitePage::query()
                ->where('id', '!=', $record->id)
                ->update(['is_home' => false]);
        }
    }
}
