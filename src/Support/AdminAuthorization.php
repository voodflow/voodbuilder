<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Admin resource authorization for Filament resources.
 *
 * Policies historically check Shield-style abilities (`ViewAny:SitePage`). That
 * hides Pages / Menus / Layouts on a stock Filament install with no permission
 * package. Driver:
 *
 * - `auto` (default): use named abilities when Filament Shield is present;
 *   otherwise allow Filament panel users (see AdminAccess).
 * - `permissions`: always require named abilities (Shield / custom Gate).
 * - `panel`: always allow Filament panel users (ignore named abilities).
 */
final class AdminAuthorization
{
    public static function allows(?Authenticatable $user, string $ability): bool
    {
        if ($user === null) {
            return false;
        }

        $driver = (string) config('voodbuilder.authorization.driver', 'auto');

        return match ($driver) {
            'panel' => AdminAccess::userCanAccessPanel(),
            'permissions' => self::canAbility($user, $ability),
            default => self::auto($user, $ability),
        };
    }

    private static function auto(Authenticatable $user, string $ability): bool
    {
        // Filament Shield present → honour named abilities (Cosmolab / multi-role apps).
        // Spatie Permission alone is ignored — it often appears without assigned abilities.
        if (self::usesPermissionAuthorizer()) {
            return self::canAbility($user, $ability);
        }

        return AdminAccess::userCanAccessPanel();
    }

    private static function canAbility(Authenticatable $user, string $ability): bool
    {
        return method_exists($user, 'can')
            ? (bool) $user->can($ability)
            : false;
    }

    public static function usesPermissionAuthorizer(): bool
    {
        return class_exists(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::class);
    }
}
