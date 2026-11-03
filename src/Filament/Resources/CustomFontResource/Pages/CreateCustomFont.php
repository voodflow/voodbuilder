<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\CustomFontResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Voodbuilder\Filament\Resources\CustomFontResource;

/**
 * Create a custom font from uploaded font files.
 */
class CreateCustomFont extends CreateRecord
{
    protected static string $resource = CustomFontResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return CustomFontResource::withParsedFaces($data);
    }
}
