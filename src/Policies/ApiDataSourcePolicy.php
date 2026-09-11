<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Voodflow\Voodbuilder\Models\ApiDataSource;

/**
 * Authorization policy for API Data Sources.
 */
final class ApiDataSourcePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, ApiDataSource $apiDataSource): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, ApiDataSource $apiDataSource): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, ApiDataSource $apiDataSource): bool
    {
        return true;
    }
}
