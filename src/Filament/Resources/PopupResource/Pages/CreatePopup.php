<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource;

class CreatePopup extends CreateRecord
{
    protected static string $resource = PopupResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PopupResource::normalizeFormData($data);
    }

    protected function afterCreate(): void
    {
        /** @var \Voodflow\Voodbuilder\Models\BuilderPopup $record */
        $record = $this->record;

        $record->update([
            'html' => '<section class="voodbuilder-gjs-section bg-vp-bg"><div class="voodbuilder-gjs-container px-6 py-10"><div class="rounded-xl border border-vp-divider bg-vp-bg-elv p-8 text-center"><h2 class="text-2xl font-semibold text-vp-text-1 mb-3">'.e($record->name).'</h2><p class="text-vp-text-2 mb-6">'.e(__('voodbuilder::popups.defaults.body')).'</p><button type="button" class="inline-flex items-center rounded-lg bg-vp-brand-1 px-4 py-2 text-sm font-medium text-white" data-voodbuilder-popup-close>'.e(__('voodbuilder::popups.defaults.cta')).'</button></div></div></section>',
        ]);
    }
}
