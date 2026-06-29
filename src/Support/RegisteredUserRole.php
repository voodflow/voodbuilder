<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Models\Role;

final class RegisteredUserRole
{
    public static function assign(Authenticatable $user): void
    {
        if (! method_exists($user, 'assignRole')) {
            return;
        }

        $role = (string) config('voodbuilder.auth.registered_role', 'registered');

        if ($role === '' || ! class_exists(Role::class)) {
            return;
        }

        if (Role::query()->where('name', $role)->where('guard_name', self::guardName())->doesntExist()) {
            return;
        }

        $user->assignRole($role);
    }

    protected static function guardName(): string
    {
        return (string) config('auth.defaults.guard', 'web');
    }
}
