<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Enums\PageBuilder;

/**
 * @implements CastsAttributes<PageBuilder|null, PageBuilder|string|null>
 */
final class AsPageBuilder implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PageBuilder
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof PageBuilder) {
            return $value;
        }

        return PageBuilder::normalize($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = PageBuilder::normalize($value);

        return $normalized?->value;
    }
}
