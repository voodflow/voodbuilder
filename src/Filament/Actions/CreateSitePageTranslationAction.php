<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Voodflow\Vpress\Filament\Resources\SitePageResource;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\SitePageResolver;
use Voodflow\Vpress\Support\SitePageTranslation;
use Voodflow\Vtuts\Support\Locales;

class CreateSitePageTranslationAction
{
    public static function make(): Action
    {
        return Action::make('createSitePageTranslation')
            ->label(__('vpress::admin.actions.translate_page'))
            ->icon('heroicon-o-language')
            ->color('gray')
            ->visible(fn (SitePage $record): bool => SitePageResolver::localizationEnabled()
                && SitePageTranslation::availableTargetLocales($record) !== [])
            ->tooltip(__('vpress::admin.translation.tooltip'))
            ->modalHeading(__('vpress::admin.translation.modal_heading'))
            ->modalDescription(__('vpress::admin.translation.modal_description'))
            ->schema(fn (SitePage $record): array => [
                Select::make('locale')
                    ->label(__('vpress::admin.fields.target_language'))
                    ->options(fn (): array => SitePageTranslation::availableTargetLocales($record))
                    ->required()
                    ->native(false),
            ])
            ->action(function (SitePage $record, array $data, Action $action): void {
                $translation = SitePageTranslation::createFrom($record, $data['locale']);

                Notification::make()
                    ->title(__('vpress::admin.notifications.translation_created'))
                    ->body(__('vpress::admin.notifications.translation_created_body', [
                        'locale' => Locales::options()[$translation->locale] ?? $translation->locale,
                    ]))
                    ->success()
                    ->send();

                $action->redirect(SitePageResource::getUrl('edit', ['record' => $translation]));
            });
    }
}
