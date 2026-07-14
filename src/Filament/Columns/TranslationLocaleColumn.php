<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Columns;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Support\TranslationGroupDeletion;
use Voodflow\Vtuts\Support\Locales;

final class TranslationLocaleColumn
{
    /**
     * @param  class-string<Resource>  $resourceClass
     */
    public static function make(string $resourceClass): TextColumn
    {
        return TextColumn::make('locale')
            ->label(__('voodbuilder::admin.fields.lang'))
            ->badge()
            ->state(fn (Model $record): array => $record->translationLocaleCodes())
            ->color(fn (string $state, Model $record): string => strtolower($state) === $record->getAttribute('locale')
                ? 'primary'
                : 'gray')
            ->url(function (Model $record, string $state) use ($resourceClass): string {
                $locale = strtolower($state);

                $member = TranslationGroupDeletion::groupMembers($record)
                    ->first(fn (Model $translation): bool => $translation->getAttribute('locale') === $locale);

                return $resourceClass::getUrl('edit', ['record' => $member ?? $record]);
            })
            ->tooltip(function (Model $record): ?string {
                if (blank($record->getAttribute('translation_group_id'))) {
                    return null;
                }

                $labels = TranslationGroupDeletion::groupMembers($record)
                    ->map(fn (Model $translation): string => self::localeLabel($translation))
                    ->implode(', ');

                return $labels !== '' ? $labels : null;
            })
            ->visible(fn (): bool => class_exists(Locales::class) && count(Locales::codes()) > 1);
    }

    protected static function localeLabel(Model $record): string
    {
        $locale = (string) $record->getAttribute('locale');

        return class_exists(Locales::class)
            ? (Locales::options()[$locale] ?? strtoupper($locale))
            : strtoupper($locale);
    }
}
