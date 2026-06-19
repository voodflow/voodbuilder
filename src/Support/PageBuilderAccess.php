<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Models\Permission;

final class PageBuilderAccess
{
    public static function userCanUsePageBuilder(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (AdminAccess::userCanAccessPanel()) {
            return true;
        }

        return self::userHasBuilderPermission(auth()->user());
    }

    public static function userHasBuilderPermission(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof Authenticatable) {
            return false;
        }

        if (! method_exists($user, 'can')) {
            return false;
        }

        $permission = self::permissionName();

        if ($permission === '' || ! class_exists(Permission::class)) {
            return false;
        }

        if (Permission::query()->where('name', $permission)->where('guard_name', self::guardName())->doesntExist()) {
            return false;
        }

        return $user->can($permission);
    }

    public static function permissionName(): string
    {
        return (string) config('vpress.permissions.page_builder', 'builder');
    }

    public static function guardName(): string
    {
        return (string) config('auth.defaults.guard', 'web');
    }
}
