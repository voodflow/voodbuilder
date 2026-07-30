<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Voodflow\Voodbuilder\Support\TranslationGroupQuery;

/**
 * Scopes List Table To Canonical Translation Groups trait.
 */
trait ScopesListTableToCanonicalTranslationGroups
{
    protected function getTableQuery(): Builder|Relation|null
    {
        $query = parent::getTableQuery();

        if (! $query instanceof Builder) {
            return $query;
        }

        return TranslationGroupQuery::canonicalOnly($query);
    }
}
