<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Models\ChromeLayout;

/**
 * Filament action: Clone Chrome Layout.
 */
class CloneChromeLayoutAction
{
    public static function make(): Action
    {
        return Action::make('cloneChromeLayout')
            ->label(__('voodbuilder::chrome_layouts.actions.clone'))
            ->icon('heroicon-o-square-2-stack')
            ->color('gray')
            ->modalHeading(__('voodbuilder::chrome_layouts.clone.modal_heading'))
            ->modalDescription(__('voodbuilder::chrome_layouts.clone.modal_description'))
            ->schema(fn (ChromeLayout $record): array => [
                TextInput::make('name')
                    ->label(__('voodbuilder::chrome_layouts.fields.name'))
                    ->default($record->name.' (copy)')
                    ->required()
                    ->maxLength(120),
                TextInput::make('slug')
                    ->label(__('voodbuilder::chrome_layouts.fields.slug'))
                    ->default(Str::slug($record->slug.'-copy'))
                    ->required()
                    ->maxLength(120)
                    ->alphaDash()
                    ->unique(ChromeLayout::class, 'slug'),
            ])
            ->action(function (ChromeLayout $record, array $data, Action $action): void {
                $clone = $record->replicate(['is_default']);
                $clone->fill([
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'is_default' => false,
                ]);
                $clone->save();

                Notification::make()
                    ->title(__('voodbuilder::chrome_layouts.notifications.cloned'))
                    ->success()
                    ->send();

                $action->redirect(ChromeLayoutResource::getUrl('edit', ['record' => $clone]));
            });
    }
}
