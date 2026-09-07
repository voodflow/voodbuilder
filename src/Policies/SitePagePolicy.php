<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\AdminAuthorization;

/**
 * Authorization policy for Site Page.
 */
class SitePagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'ViewAny:SitePage');
    }

    public function view(AuthUser $authUser, SitePage $sitePage): bool
    {
        return AdminAuthorization::allows($authUser, 'View:SitePage');
    }

    public function create(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'Create:SitePage');
    }

    public function update(AuthUser $authUser, SitePage $sitePage): bool
    {
        return AdminAuthorization::allows($authUser, 'Update:SitePage');
    }

    public function delete(AuthUser $authUser, SitePage $sitePage): bool
    {
        return AdminAuthorization::allows($authUser, 'Delete:SitePage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'DeleteAny:SitePage');
    }

    public function restore(AuthUser $authUser, SitePage $sitePage): bool
    {
        return AdminAuthorization::allows($authUser, 'Restore:SitePage');
    }

    public function forceDelete(AuthUser $authUser, SitePage $sitePage): bool
    {
        return AdminAuthorization::allows($authUser, 'ForceDelete:SitePage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'ForceDeleteAny:SitePage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'RestoreAny:SitePage');
    }

    public function replicate(AuthUser $authUser, SitePage $sitePage): bool
    {
        return AdminAuthorization::allows($authUser, 'Replicate:SitePage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return AdminAuthorization::allows($authUser, 'Reorder:SitePage');
    }
}
