<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\NavigationMenuResolver;
use Voodflow\Voodbuilder\Support\NavigationMenuTranslation;
use Voodflow\Vtuts\Support\Locales;

class CreateNavigationMenuTranslationAction
{
    public static function make(): Action
    {
        return Action::make('createNavigationMenuTranslation')
            ->label(__('voodbuilder::admin.actions.translate_menu'))
            ->icon('heroicon-o-language')
            ->color('info')
            ->visible(fn (NavigationMenu $record): bool => NavigationMenuResolver::localizationEnabled()
                && NavigationMenuTranslation::availableTargetLocales($record) !== [])
            ->tooltip(__('voodbuilder::admin.menu_translation.tooltip'))
            ->modalHeading(__('voodbuilder::admin.menu_translation.modal_heading'))
            ->modalDescription(__('voodbuilder::admin.menu_translation.modal_description'))
            ->schema(fn (NavigationMenu $record): array => [
                Select::make('locale')
                    ->label(__('voodbuilder::admin.fields.target_language'))
                    ->options(fn (): array => NavigationMenuTranslation::availableTargetLocales($record))
                    ->required()
                    ->native(false),
            ])
            ->action(function (NavigationMenu $record, array $data, Action $action): void {
                $translation = NavigationMenuTranslation::createFrom($record, $data['locale']);

                Notification::make()
                    ->title(__('voodbuilder::admin.notifications.menu_translation_created'))
                    ->body(__('voodbuilder::admin.notifications.menu_translation_created_body', [
                        'locale' => Locales::options()[$translation->locale] ?? $translation->locale,
                    ]))
                    ->success()
                    ->send();

                $action->redirect(NavigationMenuResource::getUrl('edit', ['record' => $translation]));
            });
    }
}
