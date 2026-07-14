<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\TranslationGroupDeletion;
use Voodflow\Vtuts\Support\Locales;

class DeleteSitePageTranslationsAction
{
    public static function make(bool $fromTable = false): Action
    {
        return Action::make('deleteSitePageTranslations')
            ->label(__('voodbuilder::admin.actions.delete_translations'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (SitePage $record): bool => class_exists(Locales::class)
                && count(Locales::codes()) > 1
                && TranslationGroupDeletion::hasDeletableTranslations($record))
            ->modalHeading(__('voodbuilder::admin.translation.delete_translations_heading'))
            ->modalDescription(__('voodbuilder::admin.translation.delete_translations_modal_description'))
            ->modalSubmitActionLabel(__('voodbuilder::admin.actions.delete_translations'))
            ->schema(fn (SitePage $record): array => [
                Placeholder::make('canonical_notice')
                    ->hiddenLabel()
                    ->content(fn (): string => __('voodbuilder::admin.translation.canonical_kept_notice', [
                        'locale' => TranslationGroupDeletion::localeLabel(
                            TranslationGroupDeletion::canonicalMember($record)
                        ),
                        'title' => TranslationGroupDeletion::canonicalMember($record)->title,
                    ])),
                CheckboxList::make('translation_ids')
                    ->label(__('voodbuilder::admin.fields.translations_to_delete'))
                    ->options(fn (): array => TranslationGroupDeletion::selectableOptions(
                        $record,
                        fn (SitePage $member): string => $member->title.' ('.TranslationGroupDeletion::localeLabel($member).')',
                    ))
                    ->required()
                    ->columns(1),
            ])
            ->action(function (SitePage $record, array $data, Action $action) use ($fromTable): void {
                $members = TranslationGroupDeletion::deletableMembers($record);
                $selectedIds = array_map(intval(...), $data['translation_ids'] ?? []);

                if ($selectedIds === []) {
                    Notification::make()
                        ->danger()
                        ->title(__('voodbuilder::admin.notifications.translations_delete_none_selected'))
                        ->send();

                    return;
                }

                $deleted = TranslationGroupDeletion::deleteByIds(SitePage::class, $selectedIds);

                Notification::make()
                    ->success()
                    ->title(__('voodbuilder::admin.notifications.translations_deleted'))
                    ->body(__('voodbuilder::admin.notifications.translations_deleted_body', [
                        'count' => count($deleted),
                        'list' => TranslationGroupDeletion::localeList(
                            $members->filter(fn (SitePage $member): bool => in_array($member->getKey(), $deleted, true))
                        ),
                    ]))
                    ->send();

                if ($fromTable || ! in_array($record->getKey(), $deleted, true)) {
                    return;
                }

                $action->redirect(SitePageResource::getUrl('edit', [
                    'record' => TranslationGroupDeletion::canonicalMember($record),
                ]));
            });
    }
}
