<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\CustomFontResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodbuilder\Filament\Resources\CustomFontResource;

/**
 * Edit a custom font / replace its uploaded files.
 */
class EditCustomFont extends EditRecord
{
    protected static string $resource = CustomFontResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return CustomFontResource::withUploadedPaths($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CustomFontResource::withParsedFaces($data);
    }
}
