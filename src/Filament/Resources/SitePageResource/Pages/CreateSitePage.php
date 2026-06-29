<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\SitePageResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Voodflow\Voodbuilder\Filament\Concerns\ConfirmsSitePageHomeTakeover;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageHome;

class CreateSitePage extends CreateRecord
{
    use ConfirmsSitePageHomeTakeover;

    protected static string $resource = SitePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->confirmHomeTakeoverAction(),
        ];
    }

    protected function beforeCreate(): void
    {
        $this->ensureHomeTakeoverConfirmed();
    }

    protected function afterCreate(): void
    {
        /** @var SitePage $record */
        $record = $this->record->refresh();

        if (! $record->is_home) {
            return;
        }

        $demoted = SitePageHome::assignHome($record);

        if ($demoted > 0) {
            Notification::make()
                ->title(__('voodbuilder::admin.notifications.home_reassigned'))
                ->body(__('voodbuilder::admin.notifications.home_reassigned_body', ['count' => $demoted]))
                ->info()
                ->send();
        }
    }

    protected function proceedAfterHomeTakeoverConfirmation(): void
    {
        $this->create();
    }
}
