<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Voodflow\Voodbuilder\Enums\MenuItemType;

/**
 * @implements CastsAttributes<MenuItemType|string, MenuItemType|string>
 */
final class MenuItemTypeCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): MenuItemType|string
    {
        if ($value instanceof MenuItemType) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return MenuItemType::Url;
        }

        return MenuItemType::tryFrom($value) ?? $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof MenuItemType) {
            return $value->value;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        throw new InvalidArgumentException('Menu item type must be a MenuItemType enum or non-empty string.');
    }
}
