<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\NavigationMenuPlacements;
use Voodflow\Voodbuilder\Support\NavigationMenuTranslation;

class CloneNavigationMenuAction
{
    public static function make(): Action
    {
        return Action::make('cloneNavigationMenu')
            ->label(__('voodbuilder::admin.actions.clone_menu_placement'))
            ->icon('heroicon-o-square-2-stack')
            ->color('gray')
            ->visible(fn (NavigationMenu $record): bool => self::hasAvailablePlacements($record))
            ->tooltip(__('voodbuilder::admin.menu_clone.tooltip'))
            ->modalHeading(__('voodbuilder::admin.menu_clone.modal_heading'))
            ->modalDescription(__('voodbuilder::admin.menu_clone.modal_description'))
            ->schema(fn (NavigationMenu $record): array => [
                TextInput::make('name')
                    ->label(__('voodbuilder::admin.fields.menu_clone_name'))
                    ->default($record->name.' (copy)')
                    ->required()
                    ->maxLength(255),
                Select::make('slug')
                    ->label(__('Menu placement'))
                    ->options(fn (): array => self::availablePlacements($record))
                    ->required()
                    ->native(false)
                    ->searchable(),
            ])
            ->action(function (NavigationMenu $record, array $data, Action $action): void {
                $clone = NavigationMenuTranslation::cloneAsDuplicate(
                    $record,
                    $data['slug'],
                    $data['name'],
                );

                Notification::make()
                    ->title(__('voodbuilder::admin.notifications.menu_cloned'))
                    ->success()
                    ->send();

                $action->redirect(NavigationMenuResource::getUrl('edit', ['record' => $clone]));
            });
    }

    /** @return array<string, string> */
    protected static function availablePlacements(NavigationMenu $record): array
    {
        $placements = NavigationMenuPlacements::flatOptions($record);

        $taken = NavigationMenu::query()
            ->where('locale', $record->locale)
            ->whereKeyNot($record->getKey())
            ->pluck('slug')
            ->all();

        return collect($placements)
            ->reject(fn (string $label, string $slug): bool => in_array($slug, $taken, true))
            ->all();
    }

    public static function hasAvailablePlacements(NavigationMenu $record): bool
    {
        return self::availablePlacements($record) !== [];
    }
}
