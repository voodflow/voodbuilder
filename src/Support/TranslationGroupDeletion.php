<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Translation Group Deletion.
 */
final class TranslationGroupDeletion
{
    /** @return Collection<int, Model> */
    public static function groupMembers(Model $record): Collection
    {
        if (blank($record->getAttribute('translation_group_id'))) {
            return collect([$record]);
        }

        return $record::query()
            ->where('translation_group_id', $record->getAttribute('translation_group_id'))
            ->orderBy('locale')
            ->get();
    }

    public static function canonicalMember(Model $record): Model
    {
        return self::groupMembers($record)->sortBy('id')->firstOrFail();
    }

    public static function isCanonical(Model $record): bool
    {
        return self::canonicalMember($record)->is($record);
    }

    /** @return Collection<int, Model> */
    public static function deletableMembers(Model $record): Collection
    {
        $canonicalId = self::canonicalMember($record)->getKey();

        return self::groupMembers($record)
            ->reject(fn (Model $member): bool => $member->getKey() === $canonicalId)
            ->values();
    }

    public static function hasDeletableTranslations(Model $record): bool
    {
        return self::deletableMembers($record)->isNotEmpty();
    }

    /**
     * @return array<int, string> id => label
     */
    public static function selectableOptions(Model $record, callable $labelBuilder): array
    {
        return self::deletableMembers($record)
            ->mapWithKeys(function (Model $member) use ($labelBuilder): array {
                return [$member->getKey() => $labelBuilder($member)];
            })
            ->all();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int|string>  $ids
     * @return list<int|string>
     */
    public static function deleteByIds(string $modelClass, array $ids): array
    {
        $deleted = [];

        foreach ($ids as $id) {
            $member = $modelClass::query()->find($id);

            if ($member === null) {
                continue;
            }

            if (self::isCanonical($member)) {
                continue;
            }

            self::forceDeleteMember($member);
            $deleted[] = $member->getKey();
        }

        return $deleted;
    }

    /**
     * Hard-delete a member without firing model events.
     *
     * Laravel ships `deleteQuietly()` (a soft delete) but no `forceDeleteQuietly()`,
     * and `forceDelete()` only exists on models using SoftDeletes. A bulk force
     * delete on the model's own query is the quiet equivalent for both cases:
     * it bypasses events and scopes and always removes the row.
     */
    protected static function forceDeleteMember(Model $member): void
    {
        $member->newModelQuery()
            ->whereKey($member->getKey())
            ->forceDelete();
    }

    public static function localeLabel(Model $record): string
    {
        $locale = (string) $record->getAttribute('locale');

        return SiteLocales::options()[$locale] ?? strtoupper($locale);
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    public static function localeList(Collection $records): string
    {
        return $records
            ->map(fn (Model $record): string => self::localeLabel($record))
            ->implode(', ');
    }
}
