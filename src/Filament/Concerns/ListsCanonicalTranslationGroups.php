<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Concerns;

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Voodflow\Voodbuilder\Support\TranslationGroupQuery;
use Voodflow\Vtuts\Support\Locales;

trait ListsCanonicalTranslationGroups
{
    public static function getNavigationBadge(): ?string
    {
        return (string) TranslationGroupQuery::count(static::getModel()::query());
    }

    protected static function translationLocaleFilter(): SelectFilter
    {
        return SelectFilter::make('locale')
            ->label(__('voodbuilder::admin.fields.language'))
            ->options(fn (): array => class_exists(Locales::class) ? Locales::options() : [])
            ->query(function (Builder $query, array $data): void {
                $locale = $data['value'] ?? null;

                if (! is_string($locale) || $locale === '') {
                    return;
                }

                TranslationGroupQuery::whereGroupHasLocale($query, $locale);
            })
            ->hidden(fn (): bool => ! class_exists(Locales::class) || count(Locales::codes()) <= 1);
    }
}
