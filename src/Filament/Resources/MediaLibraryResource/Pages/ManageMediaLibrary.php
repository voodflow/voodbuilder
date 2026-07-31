<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\MediaLibraryResource\Pages;

use Filament\Resources\Pages\ManageRecords;
use Voodflow\Voodbuilder\Filament\Resources\MediaLibraryResource;
use Voodflow\Voodbuilder\Models\MediaLibrary;

class ManageMediaLibrary extends ManageRecords
{
    protected static string $resource = MediaLibraryResource::class;

    public function mount(): void
    {
        // Ensure the singleton library owner exists before listing/uploading.
        MediaLibrary::current();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            MediaLibraryResource::uploadAction(),
        ];
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('voodbuilder::admin.media_library.title');
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return __('voodbuilder::admin.media_library.intro');
    }
}
