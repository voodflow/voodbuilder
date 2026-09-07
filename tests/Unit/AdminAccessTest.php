<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Support\AdminAccess;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class AdminAccessTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Filament::registerPanel() only hooks resolving() — use the registry directly.
        app(PanelRegistry::class)->register(
            Panel::make()
                ->id('admin')
                ->path('admin')
                ->default(),
        );
    }

    public function test_plain_user_without_filament_user_can_access_panel_outside_filament(): void
    {
        // Simulates stock Filament installs that never added FilamentUser.
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'plain-admin@example.com',
        ])->save();

        $this->actingAs($user);

        $this->assertNull(Filament::getCurrentPanel());
        $this->assertNotEmpty(Filament::getPanels());
        $this->assertTrue(AdminAccess::userCanAccessPanel());
        $this->assertTrue(PageBuilderAccess::userCanUsePageBuilder());
    }

    public function test_plain_user_allowed_when_panel_registry_empty_on_shield_less_install(): void
    {
        app()->forgetInstance(PanelRegistry::class);
        // Re-bind empty registry
        $this->app->singleton(PanelRegistry::class, fn () => new PanelRegistry);

        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'empty-registry@example.com',
        ])->save();

        $this->actingAs($user);

        $this->assertSame([], Filament::getPanels());
        $this->assertTrue(AdminAccess::userCanAccessPanel());
        $this->assertTrue(PageBuilderAccess::userCanUsePageBuilder());
    }

    public function test_filament_user_denied_panel_cannot_use_page_builder(): void
    {
        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return false;
            }
        };

        $user->forceFill([
            'name' => 'Denied',
            'email' => 'denied@example.com',
        ])->save();

        $this->actingAs($user);

        $this->assertFalse(AdminAccess::userCanAccessPanel());
        $this->assertFalse(PageBuilderAccess::userCanUsePageBuilder());
    }
}
