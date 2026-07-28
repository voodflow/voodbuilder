<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class BackendEntitlementEnforcementTest extends TestCase
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

        Filament::registerPanel(
            Panel::make()->id('admin')->path('admin'),
        );

        PageBuilderAccess::authorizeUsing(static fn (): bool => auth()->check());
    }

    protected function tearDown(): void
    {
        PageBuilderAccess::authorizeUsing(null);

        parent::tearDown();
    }

    public function test_community_edition_forbids_component_library_api(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        // Module may still be registered from agency boot flags; controller must enforce.
        if (! Route::has('voodbuilder.grapesjs.components.index')) {
            $this->markTestSkipped('Components routes absent when module disabled at boot.');
        }

        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }
        };
        $user->forceFill(['name' => 'Ed', 'email' => 'entitlement-api@example.com'])->save();

        $this->actingAs($user)
            ->getJson(route('voodbuilder.grapesjs.components.index'))
            ->assertForbidden();
    }

    public function test_community_edition_forbids_template_import_when_route_exists(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        if (! Route::has('voodbuilder.grapesjs.page-templates.import')) {
            $this->assertFalse(Route::has('voodbuilder.grapesjs.page-templates.import'));

            return;
        }

        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }
        };
        $user->forceFill(['name' => 'Ed', 'email' => 'entitlement-tpl@example.com'])->save();

        $this->actingAs($user)
            ->postJson(route('voodbuilder.grapesjs.page-templates.import'), ['import' => []])
            ->assertForbidden();
    }
}
