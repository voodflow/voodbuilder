<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\TranslationGroupDeletion;
use Voodflow\Vtuts\Support\Locales;

class DeleteNavigationMenuTranslationsAction
{
    public static function make(bool $fromTable = false): Action
    {
        return Action::make('deleteNavigationMenuTranslations')
            ->label(__('voodbuilder::admin.actions.delete_translations'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (NavigationMenu $record): bool => class_exists(Locales::class)
                && count(Locales::codes()) > 1
                && TranslationGroupDeletion::hasDeletableTranslations($record))
            ->modalHeading(__('voodbuilder::admin.translation.delete_translations_heading'))
            ->modalDescription(__('voodbuilder::admin.translation.delete_translations_modal_description'))
            ->modalSubmitActionLabel(__('voodbuilder::admin.actions.delete_translations'))
            ->schema(fn (NavigationMenu $record): array => [
                Placeholder::make('canonical_notice')
                    ->hiddenLabel()
                    ->content(fn (): string => __('voodbuilder::admin.translation.canonical_kept_notice', [
                        'locale' => TranslationGroupDeletion::localeLabel(
                            TranslationGroupDeletion::canonicalMember($record)
                        ),
                        'title' => TranslationGroupDeletion::canonicalMember($record)->name,
                    ])),
                CheckboxList::make('translation_ids')
                    ->label(__('voodbuilder::admin.fields.translations_to_delete'))
                    ->options(fn (): array => TranslationGroupDeletion::selectableOptions(
                        $record,
                        fn (NavigationMenu $member): string => $member->name.' ('.TranslationGroupDeletion::localeLabel($member).')',
                    ))
                    ->required()
                    ->columns(1),
            ])
            ->action(function (NavigationMenu $record, array $data, Action $action) use ($fromTable): void {
                $members = TranslationGroupDeletion::deletableMembers($record);
                $selectedIds = array_map(intval(...), $data['translation_ids'] ?? []);

                if ($selectedIds === []) {
                    Notification::make()
                        ->danger()
                        ->title(__('voodbuilder::admin.notifications.translations_delete_none_selected'))
                        ->send();

                    return;
                }

                $deleted = TranslationGroupDeletion::deleteByIds(NavigationMenu::class, $selectedIds);

                Notification::make()
                    ->success()
                    ->title(__('voodbuilder::admin.notifications.translations_deleted'))
                    ->body(__('voodbuilder::admin.notifications.translations_deleted_body', [
                        'count' => count($deleted),
                        'list' => TranslationGroupDeletion::localeList(
                            $members->filter(fn (NavigationMenu $member): bool => in_array($member->getKey(), $deleted, true))
                        ),
                    ]))
                    ->send();

                if ($fromTable || ! in_array($record->getKey(), $deleted, true)) {
                    return;
                }

                $action->redirect(NavigationMenuResource::getUrl('edit', [
                    'record' => TranslationGroupDeletion::canonicalMember($record),
                ]));
            });
    }
}
