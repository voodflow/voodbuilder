<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\SitePageResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodbuilder\Filament\Actions\CreateSitePageTranslationAction;
use Voodflow\Voodbuilder\Filament\Concerns\ConfirmsSitePageHomeTakeover;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageHome;

/**
 * Edit Site Page.
 */
class EditSitePage extends EditRecord
{
    use ConfirmsSitePageHomeTakeover;

    protected static string $resource = SitePageResource::class;

    protected function getActions(): array
    {
        return [
            $this->confirmHomeTakeoverAction(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateSitePageTranslationAction::make(),
            Action::make('openVisualEditor')
                ->label(__('voodbuilder::pro.actions.open_visual_editor'))
                ->icon('heroicon-o-paint-brush')
                ->color('primary')
                ->url(fn (): string => $this->record->getUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->usesEditorBuilder()),
            DeleteAction::make()
                ->hidden(fn (SitePage $record): bool => $record->is_home),
        ];
    }

    protected function beforeSave(): void
    {
        $this->ensureHomeTakeoverConfirmed();
    }

    protected function afterSave(): void
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
        $this->save();
    }
}
