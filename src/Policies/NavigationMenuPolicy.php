<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Illuminate\Auth\Access\HandlesAuthorization;

class NavigationMenuPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NavigationMenu');
    }

    public function view(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return $authUser->can('View:NavigationMenu');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NavigationMenu');
    }

    public function update(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return $authUser->can('Update:NavigationMenu');
    }

    public function delete(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return $authUser->can('Delete:NavigationMenu');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NavigationMenu');
    }

    public function restore(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return $authUser->can('Restore:NavigationMenu');
    }

    public function forceDelete(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return $authUser->can('ForceDelete:NavigationMenu');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NavigationMenu');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NavigationMenu');
    }

    public function replicate(AuthUser $authUser, NavigationMenu $navigationMenu): bool
    {
        return $authUser->can('Replicate:NavigationMenu');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NavigationMenu');
    }

}