<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;

class CreateChromeLayout extends CreateRecord
{
    protected static string $resource = ChromeLayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['html'] = ChromeLayoutResource::defaultStarterHtml();

        if (($data['is_default'] ?? false) === true) {
            $data['channel_ids'] = [];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var ChromeLayout $record */
        $record = $this->record;
        ChromeLayout::ensureSingleDefault($record);
        ChromeLayoutResolver::forgetCache();
    }
}
