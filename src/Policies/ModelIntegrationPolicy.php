<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\AdminAuthorization;

/**
 * Authorization policy for Model Integration.
 */
final class ModelIntegrationPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return AdminAuthorization::allows($user, 'ViewAny:ModelIntegration');
    }

    public function view(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return AdminAuthorization::allows($user, 'View:ModelIntegration');
    }

    public function create(Authenticatable $user): bool
    {
        return AdminAuthorization::allows($user, 'Create:ModelIntegration');
    }

    public function update(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return AdminAuthorization::allows($user, 'Update:ModelIntegration');
    }

    public function delete(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return AdminAuthorization::allows($user, 'Delete:ModelIntegration');
    }

    public function restore(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return AdminAuthorization::allows($user, 'Restore:ModelIntegration');
    }

    public function forceDelete(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return AdminAuthorization::allows($user, 'ForceDelete:ModelIntegration');
    }
}
