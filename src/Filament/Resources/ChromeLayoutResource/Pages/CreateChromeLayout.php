<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;

class CreateChromeLayout extends CreateRecord
{
    protected static string $resource = ChromeLayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['html'] = ChromeLayoutResource::defaultStarterHtml();

        return $data;
    }

    protected function afterCreate(): void
    {
        ChromeLayoutResolver::forgetCache();
    }
}
