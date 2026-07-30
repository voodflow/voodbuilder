<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChromeLayoutPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChromeLayout');
    }

    public function view(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return $authUser->can('View:ChromeLayout');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChromeLayout');
    }

    public function update(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return $authUser->can('Update:ChromeLayout');
    }

    public function delete(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return $authUser->can('Delete:ChromeLayout');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChromeLayout');
    }

    public function restore(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return $authUser->can('Restore:ChromeLayout');
    }

    public function forceDelete(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return $authUser->can('ForceDelete:ChromeLayout');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChromeLayout');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChromeLayout');
    }

    public function replicate(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return $authUser->can('Replicate:ChromeLayout');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChromeLayout');
    }

}