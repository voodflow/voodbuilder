<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\AdminAuthorization;

/**
 * Authorization policy for Chrome Layout.
 */
class ChromeLayoutPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'ViewAny:ChromeLayout');
    }

    public function view(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return AdminAuthorization::allows($authUser, 'View:ChromeLayout');
    }

    public function create(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'Create:ChromeLayout');
    }

    public function update(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return AdminAuthorization::allows($authUser, 'Update:ChromeLayout');
    }

    public function delete(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return AdminAuthorization::allows($authUser, 'Delete:ChromeLayout');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'DeleteAny:ChromeLayout');
    }

    public function restore(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return AdminAuthorization::allows($authUser, 'Restore:ChromeLayout');
    }

    public function forceDelete(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return AdminAuthorization::allows($authUser, 'ForceDelete:ChromeLayout');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'ForceDeleteAny:ChromeLayout');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'RestoreAny:ChromeLayout');
    }

    public function replicate(AuthUser $authUser, ChromeLayout $chromeLayout): bool
    {
        return AdminAuthorization::allows($authUser, 'Replicate:ChromeLayout');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'Reorder:ChromeLayout');
    }
}
