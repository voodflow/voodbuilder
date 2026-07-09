<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource;

class EditPopup extends EditRecord
{
    protected static string $resource = PopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openVisualEditor')
                ->label(__('voodbuilder::popups.actions.open_visual_editor'))
                ->icon('heroicon-o-paint-brush')
                ->color('primary')
                ->url(fn (): string => route('voodbuilder.popups.editor', [
                    'popup' => $this->record,
                    'edit' => 1,
                ]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return PopupResource::hydrateFormData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PopupResource::normalizeFormData($data);
    }
}
