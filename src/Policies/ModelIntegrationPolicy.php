<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Voodflow\Voodbuilder\Models\ModelIntegration;

final class ModelIntegrationPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return true;
    }

    public function restore(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return true;
    }

    public function forceDelete(Authenticatable $user, ModelIntegration $modelIntegration): bool
    {
        return true;
    }
}
