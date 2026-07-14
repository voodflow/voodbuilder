<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;

class EditChromeLayout extends EditRecord
{
    protected static string $resource = ChromeLayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openVisualEditor')
                ->label(__('voodbuilder::chrome_layouts.actions.open_visual_editor'))
                ->icon('heroicon-o-paint-brush')
                ->color('primary')
                ->url(fn (): string => route('voodbuilder.chrome-layouts.editor', [
                    'chromeLayout' => $this->record,
                    'edit' => 1,
                ]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        ChromeLayoutResolver::forgetCache();
    }
}
