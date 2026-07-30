<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Translation Group Query.
 */
final class TranslationGroupQuery
{
    /** @param  Builder<Model>  $query */
    public static function canonicalOnly(Builder $query): Builder
    {
        $model = $query->getModel();
        $table = $model->getTable();

        return $query->where(function (Builder $inner) use ($model, $table): void {
            $inner->whereNull("{$table}.translation_group_id")
                ->orWhereIn("{$table}.id", self::canonicalIdsSubquery($model));
        });
    }

    /** @param  Builder<Model>  $query */
    public static function count(Builder $query): int
    {
        return self::canonicalOnly(clone $query)->count();
    }

    /**
     * Keep canonical rows whose group includes the given locale.
     *
     * @param  Builder<Model>  $query
     */
    public static function whereGroupHasLocale(Builder $query, string $locale): Builder
    {
        $model = $query->getModel();
        $table = $model->getTable();

        return $query->where(function (Builder $inner) use ($locale, $model, $table): void {
            $inner->where("{$table}.locale", $locale)
                ->orWhereIn("{$table}.translation_group_id", self::localeGroupIdsSubquery($model, $locale));
        });
    }

    /**
     * @param  class-string<Model>|Model  $model
     */
    protected static function canonicalIdsSubquery(Model|string $model): Builder
    {
        return self::baseSiblingQuery($model, withTrashed: true)
            ->selectRaw('MIN(id)')
            ->whereNotNull('translation_group_id')
            ->groupBy('translation_group_id');
    }

    /**
     * @param  class-string<Model>|Model  $model
     */
    protected static function localeGroupIdsSubquery(Model|string $model, string $locale): Builder
    {
        return self::baseSiblingQuery($model)
            ->select('translation_group_id')
            ->where('locale', $locale)
            ->whereNotNull('translation_group_id')
            ->distinct();
    }

    /**
     * @param  class-string<Model>|Model  $model
     */
    protected static function baseSiblingQuery(Model|string $model, bool $withTrashed = false): Builder
    {
        $class = $model instanceof Model ? $model::class : $model;

        if ($withTrashed && self::usesSoftDeletes($model)) {
            return $class::withTrashed();
        }

        $query = $class::query();

        if (self::usesSoftDeletes($model)) {
            $query->whereNull($query->getModel()->getQualifiedDeletedAtColumn());
        }

        return $query;
    }

    /**
     * @param  class-string<Model>|Model  $model
     */
    protected static function usesSoftDeletes(Model|string $model): bool
    {
        $class = $model instanceof Model ? $model::class : $model;

        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }
}
