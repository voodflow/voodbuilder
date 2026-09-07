<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\AdminAuthorization;

/**
 * Authorization policy for Navigation Menu.
 */
class NavigationMenuPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'ViewAny:NavigationMenu');
    }

    public function view(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return AdminAuthorization::allows($authUser, 'View:NavigationMenu');
    }

    public function create(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'Create:NavigationMenu');
    }

    public function update(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return AdminAuthorization::allows($authUser, 'Update:NavigationMenu');
    }

    public function delete(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return AdminAuthorization::allows($authUser, 'Delete:NavigationMenu');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'DeleteAny:NavigationMenu');
    }

    public function restore(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return AdminAuthorization::allows($authUser, 'Restore:NavigationMenu');
    }

    public function forceDelete(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return AdminAuthorization::allows($authUser, 'ForceDelete:NavigationMenu');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'ForceDeleteAny:NavigationMenu');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'RestoreAny:NavigationMenu');
    }

    public function replicate(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return AdminAuthorization::allows($authUser, 'Replicate:NavigationMenu');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'Reorder:NavigationMenu');
    }
}
