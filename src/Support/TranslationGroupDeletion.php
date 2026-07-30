<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Voodflow\Vtuts\Support\Locales;

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
     * @return list<int>
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

            $member->forceDeleteQuietly();
            $deleted[] = (int) $member->getKey();
        }

        return $deleted;
    }

    public static function localeLabel(Model $record): string
    {
        $locale = (string) $record->getAttribute('locale');

        return class_exists(Locales::class)
            ? (Locales::options()[$locale] ?? strtoupper($locale))
            : strtoupper($locale);
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
