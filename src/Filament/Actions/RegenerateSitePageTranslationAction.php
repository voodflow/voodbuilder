<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SiteLocales;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SitePageTranslation;

/**
 * Rebuild an existing translation from the page being edited.
 *
 * The usual reason a translation exists at all is that the source language was written
 * first: once the source has moved on, the sibling is stale and there was no way to redo
 * it without deleting the page and creating a new one.
 */
class RegenerateSitePageTranslationAction
{
    public static function make(): Action
    {
        return Action::make('regenerateSitePageTranslation')
            ->label(__('voodbuilder::admin.actions.regenerate_translation'))
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('gray')
            ->visible(fn (SitePage $record): bool => SitePageResolver::localizationEnabled()
                && SitePageTranslation::existingTargetLocales($record) !== [])
            ->tooltip(__('voodbuilder::admin.translation.regenerate_tooltip'))
            ->requiresConfirmation()
            ->modalHeading(__('voodbuilder::admin.translation.regenerate_heading'))
            ->modalDescription(__('voodbuilder::admin.translation.regenerate_description'))
            ->modalSubmitActionLabel(__('voodbuilder::admin.actions.regenerate_translation'))
            ->schema(fn (SitePage $record): array => [
                Select::make('locale')
                    ->label(__('voodbuilder::admin.fields.target_language'))
                    ->options(fn (): array => SitePageTranslation::existingTargetLocales($record))
                    ->required()
                    ->native(false),
                Toggle::make('copy_title')
                    ->label(__('voodbuilder::admin.translation.copy_title'))
                    ->helperText(__('voodbuilder::admin.translation.copy_title_help'))
                    ->default(false),
            ])
            ->action(function (SitePage $record, array $data): void {
                $target = SitePageTranslation::findTarget($record, (string) $data['locale']);

                if (! $target instanceof SitePage) {
                    return;
                }

                SitePageTranslation::cloneContentInto(
                    $record,
                    $target,
                    copyTitle: (bool) ($data['copy_title'] ?? false),
                );

                Notification::make()
                    ->title(__('voodbuilder::admin.notifications.translation_regenerated'))
                    ->body(__('voodbuilder::admin.notifications.translation_regenerated_body', [
                        'locale' => SiteLocales::options()[$target->locale] ?? $target->locale,
                    ]))
                    ->success()
                    ->send();
            });
    }
}
