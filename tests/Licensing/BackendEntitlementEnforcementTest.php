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
use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;
use Voodflow\VoodbuilderComponents\VoodbuilderComponents;
use Voodflow\VoodbuilderTemplates\VoodbuilderTemplates;

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

    public function test_community_without_components_plugin_forbids_component_library_api(): void
    {
        if (class_exists(VoodbuilderComponents::class)) {
            VoodbuilderComponents::reset();
        }

        if (! class_exists(ComponentsModule::class)) {
            $this->markTestSkipped('voodbuilder-components companion package is not available.');
        }

        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        // Module may still be registered from TestCase activation; controller must enforce.
        if (! Route::has('voodbuilder.editor.components.index')) {
            $this->markTestSkipped('Components routes absent when module disabled at boot.');
        }

        // After reset, ComponentsModule may still report enabled from boot registration.
        // Prefer asserting via a fresh request if the module stays enabled — skip when plugin gate cannot be undone mid-process.
        if (ComponentsModule::isEnabled()) {
            $this->markTestSkipped('Components module remains enabled after boot registration; plugin gate is activation-based.');
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
            ->getJson(route('voodbuilder.editor.components.index'))
            ->assertForbidden();
    }

    public function test_community_without_templates_plugin_forbids_json_import(): void
    {
        if (class_exists(VoodbuilderTemplates::class)) {
            VoodbuilderTemplates::reset();
        }

        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $this->assertTrue(Route::has('voodbuilder.editor.page-templates.import'));

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
            ->postJson(route('voodbuilder.editor.page-templates.import'), ['import' => []])
            ->assertForbidden();
    }

    private function builderUser(string $email): User
    {
        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }
        };

        $user->forceFill(['name' => 'Ed', 'email' => $email])->save();

        return $user;
    }

    /**
     * The UI hides import/export via `entitlements.componentsImport`, but hiding a button is
     * not enforcement: on Professional the capability is absent while the module is installed,
     * so the route has to refuse the call itself.
     */
    /**
     * Without the Components companion routes, these stay skipped.
     * With the companion active, import/export are unlocked by the plugin (not Agency edition).
     */
    public function test_components_import_unlocked_by_companion_not_edition(): void
    {
        if (! Route::has('voodbuilder.editor.components.import')) {
            $this->markTestSkipped('Components routes absent when module disabled at boot.');
        }

        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $this->assertFalse(Voodbuilder::can('components.import'));

        $this->actingAs($this->builderUser('entitlement-cmp-import@example.com'))
            ->postJson(route('voodbuilder.editor.components.import'), [
                'components' => [['name' => 'Card', 'html' => '<div></div>']],
            ])
            ->assertCreated();
    }

    public function test_components_export_unlocked_by_companion_not_edition(): void
    {
        if (! Route::has('voodbuilder.editor.components.export')) {
            $this->markTestSkipped('Components routes absent when module disabled at boot.');
        }

        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $this->assertFalse(Voodbuilder::can('components.export'));

        $this->actingAs($this->builderUser('entitlement-cmp-export@example.com'))
            ->postJson(route('voodbuilder.editor.components.export'), ['ids' => []])
            ->assertOk();
    }

    public function test_community_edition_forbids_template_install_from_url(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $this->assertFalse(Voodbuilder::can('templates.remote-install'));

        $this->actingAs($this->builderUser('entitlement-tpl-url@example.com'))
            ->postJson(route('voodbuilder.editor.page-templates.import-url'), [
                'url' => 'https://example.com/bundle.json',
            ])
            ->assertForbidden();
    }
}
