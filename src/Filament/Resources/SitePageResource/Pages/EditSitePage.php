<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources\SitePageResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Voodflow\Vpress\Filament\Resources\SitePageResource;
use Voodflow\Vpress\Models\SitePage;

class EditSitePage extends EditRecord
{
    protected static string $resource = SitePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loadDefaultHome')
                ->label(__('Load default home'))
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $content = $this->resolveDefaultHomeContent();

                    if ($content === null) {
                        Notification::make()
                            ->title(__('No default home content configured'))
                            ->body(__('Set vpress.home.default_content_callback in your app config.'))
                            ->warning()
                            ->send();

                        return;
                    }

                    /** @var SitePage $record */
                    $record = $this->record;

                    $record->update([
                        'content' => $content,
                        'layout' => 'home',
                    ]);

                    $this->refreshFormData(['content', 'layout']);

                    Notification::make()
                        ->title(__('Default home layout loaded'))
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => ($this->record->is_home || $this->record->layout === 'home')
                    && $this->resolveDefaultHomeContent() !== null),
            DeleteAction::make()
                ->hidden(fn (SitePage $record): bool => $record->is_home),
        ];
    }

    protected function afterSave(): void
    {
        /** @var SitePage $record */
        $record = $this->record;

        if ($record->is_home) {
            SitePage::query()
                ->where('id', '!=', $record->id)
                ->update(['is_home' => false]);
        }
    }

    /** @return array<string, mixed>|null */
    protected function resolveDefaultHomeContent(): ?array
    {
        $callback = config('vpress.home.default_content_callback');

        if (! is_array($callback) || count($callback) !== 2) {
            return null;
        }

        $content = app()->call($callback);

        return is_array($content) ? $content : null;
    }
}
