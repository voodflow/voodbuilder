<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Authorization policy for Site Page.
 */
class SitePagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SitePage');
    }

    public function view(AuthUser $authUser, SitePage $sitePage): bool
    {
        return $authUser->can('View:SitePage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SitePage');
    }

    public function update(AuthUser $authUser, SitePage $sitePage): bool
    {
        return $authUser->can('Update:SitePage');
    }

    public function delete(AuthUser $authUser, SitePage $sitePage): bool
    {
        return $authUser->can('Delete:SitePage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SitePage');
    }

    public function restore(AuthUser $authUser, SitePage $sitePage): bool
    {
        return $authUser->can('Restore:SitePage');
    }

    public function forceDelete(AuthUser $authUser, SitePage $sitePage): bool
    {
        return $authUser->can('ForceDelete:SitePage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SitePage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SitePage');
    }

    public function replicate(AuthUser $authUser, SitePage $sitePage): bool
    {
        return $authUser->can('Replicate:SitePage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SitePage');
    }
}
