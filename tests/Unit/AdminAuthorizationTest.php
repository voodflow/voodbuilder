<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Voodflow\Voodbuilder\Support\AdminAuthorization;
use Voodflow\Voodbuilder\Tests\TestCase;

final class AdminAuthorizationTest extends TestCase
{
    public function test_auto_detects_permission_authorizer_from_installed_packages(): void
    {
        config(['voodbuilder.authorization.driver' => 'auto']);

        // Cosmolab / typical Filament Shield apps ship Spatie Permission.
        // Fresh Filament-only installs do not — those fall back to panel access.
        $this->assertIsBool(AdminAuthorization::usesPermissionAuthorizer());
    }

    public function test_permissions_driver_defers_to_user_can(): void
    {
        config(['voodbuilder.authorization.driver' => 'permissions']);

        $allowed = new class extends Authenticatable
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'ViewAny:SitePage';
            }
        };

        $denied = new class extends Authenticatable
        {
            public function can($abilities, $arguments = []): bool
            {
                return false;
            }
        };

        $this->assertTrue(AdminAuthorization::allows($allowed, 'ViewAny:SitePage'));
        $this->assertFalse(AdminAuthorization::allows($denied, 'ViewAny:SitePage'));
        $this->assertFalse(AdminAuthorization::allows(null, 'ViewAny:SitePage'));
    }
}
