<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SitePageTranslation;
use Voodflow\Vtuts\Support\Locales;

class CreateSitePageTranslationAction
{
    public static function make(): Action
    {
        return Action::make('createSitePageTranslation')
            ->label(__('voodbuilder::admin.actions.translate_page'))
            ->icon('heroicon-o-language')
            ->color('gray')
            ->visible(fn (SitePage $record): bool => SitePageResolver::localizationEnabled()
                && SitePageTranslation::availableTargetLocales($record) !== [])
            ->tooltip(__('voodbuilder::admin.translation.tooltip'))
            ->modalHeading(__('voodbuilder::admin.translation.modal_heading'))
            ->modalDescription(__('voodbuilder::admin.translation.modal_description'))
            ->schema(fn (SitePage $record): array => [
                Select::make('locale')
                    ->label(__('voodbuilder::admin.fields.target_language'))
                    ->options(fn (): array => SitePageTranslation::availableTargetLocales($record))
                    ->required()
                    ->native(false),
            ])
            ->action(function (SitePage $record, array $data, Action $action): void {
                $translation = SitePageTranslation::createFrom($record, $data['locale']);

                Notification::make()
                    ->title(__('voodbuilder::admin.notifications.translation_created'))
                    ->body(__('voodbuilder::admin.notifications.translation_created_body', [
                        'locale' => Locales::options()[$translation->locale] ?? $translation->locale,
                    ]))
                    ->success()
                    ->send();

                $action->redirect(SitePageResource::getUrl('edit', ['record' => $translation]));
            });
    }
}
