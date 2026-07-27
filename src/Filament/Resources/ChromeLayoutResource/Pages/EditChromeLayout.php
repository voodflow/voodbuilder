<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Models\ChromeLayout;
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['is_default'] ?? false) === true) {
            $data['channel_ids'] = [];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var ChromeLayout $record */
        $record = $this->record;
        ChromeLayout::ensureSingleDefault($record);
        ChromeLayoutResolver::forgetCache();
    }
}
