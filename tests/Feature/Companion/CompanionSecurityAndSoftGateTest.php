<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature\Companion;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;
use Voodflow\Voodbuilder\Licensing\TestingEntitlementProvider;
use Voodflow\Voodbuilder\Support\Editor\EditorCommunityBlockCatalog;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;
use Voodflow\VoodbuilderComponents\VoodbuilderComponents;
use Voodflow\VoodbuilderDynamicData\VoodbuilderDynamicData;
use Voodflow\VoodbuilderTemplates\VoodbuilderTemplates;
use Voodflow\Vpopups\Vpopups;

/**
 * Cross-companion security and soft-gate checks run from Core Testbench
 * (companions are path-autoloaded in package autoload-dev).
 */
class CompanionSecurityAndSoftGateTest extends TestCase
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

    public function test_guest_cannot_hit_editor_companion_routes(): void
    {
        $getRoutes = [
            'voodbuilder.editor.components.index',
            'voodbuilder.editor.popups.index',
        ];

        foreach ($getRoutes as $name) {
            if (! Route::has($name)) {
                continue;
            }

            $this->getJson(route($name))->assertUnauthorized();
        }

        if (Route::has('voodbuilder.editor.page-templates.import')) {
            $this->postJson(route('voodbuilder.editor.page-templates.import'), [])
                ->assertUnauthorized();
        }
    }

    public function test_components_import_export_allowed_when_companion_plugin_active(): void
    {
        if (! Route::has('voodbuilder.editor.components.import')) {
            $this->markTestSkipped('Components routes not registered.');
        }

        // Companion plugin is the commercial gate — edition matrix must not block.
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $user = $this->builderUser('companion-cmp@example.com');

        $this->actingAs($user)
            ->postJson(route('voodbuilder.editor.components.import'), [
                'components' => [['name' => 'X', 'html' => '<div></div>']],
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('voodbuilder.editor.components.export'), ['ids' => []])
            ->assertOk();
    }

    public function test_community_free_pack_excludes_demoted_sections(): void
    {
        Voodbuilder::entitlements()->useProvider(
            TestingEntitlementProvider::forEdition(EditionCapabilityMatrix::EDITION_COMMUNITY),
        );

        $filtered = EditorCommunityBlockCatalog::filterEditorBlocks([
            ['id' => 'vb-hero-2', 'label' => 'Hero'],
            ['id' => 'vb-blog-1', 'label' => 'Blog'],
            ['id' => 'vb-team-2', 'label' => 'Team 2'],
            ['id' => 'voodbuilder-heading', 'label' => 'Heading'],
            ['id' => 'voodbuilder-reading-time', 'label' => 'Reading'],
        ], chromeLayoutEditor: false);

        $ids = array_column($filtered, 'id');

        $this->assertContains('vb-hero-2', $ids);
        $this->assertContains('voodbuilder-heading', $ids);
        $this->assertContains('voodbuilder-reading-time', $ids);
        $this->assertNotContains('vb-blog-1', $ids);
        $this->assertNotContains('vb-team-2', $ids);
        $this->assertLessThanOrEqual(
            13,
            count(EditorCommunityBlockCatalog::COMMUNITY_SECTION_BLOCK_IDS),
        );
    }

    public function test_dynamic_data_module_activates_only_when_companion_present(): void
    {
        $this->assertTrue(class_exists(VoodbuilderDynamicData::class));
        $this->assertTrue(
            VoodbuilderDynamicData::isActivated(),
        );
    }

    public function test_templates_and_components_and_popups_activate_in_testbench(): void
    {
        $this->assertTrue(class_exists(VoodbuilderTemplates::class));
        $this->assertTrue(VoodbuilderTemplates::isActivated());
        $this->assertTrue(class_exists(VoodbuilderComponents::class));
        $this->assertTrue(VoodbuilderComponents::isActivated());
        $this->assertTrue(class_exists(Vpopups::class));
        $this->assertTrue(Vpopups::isActive());
    }

    public function test_dynamic_data_bindings_require_auth_and_page_builder_access(): void
    {
        if (! Route::has('voodbuilder.editor.bindings')) {
            $this->markTestSkipped('Dynamic Data bindings route not registered.');
        }

        $this->getJson(route('voodbuilder.editor.bindings'))
            ->assertUnauthorized();

        PageBuilderAccess::authorizeUsing(static fn (): bool => false);

        $this->actingAs($this->builderUser('dd-denied@example.com'))
            ->getJson(route('voodbuilder.editor.bindings'))
            ->assertForbidden();
    }

    public function test_components_library_requires_auth(): void
    {
        if (! Route::has('voodbuilder.editor.components.index')) {
            $this->markTestSkipped('Components index route not registered.');
        }

        $this->getJson(route('voodbuilder.editor.components.index'))
            ->assertUnauthorized();
    }

    public function test_page_builder_access_blocks_editor_blocks_endpoint(): void
    {
        if (! Route::has('voodbuilder.editor.blocks')) {
            $this->markTestSkipped('Editor blocks route missing.');
        }

        PageBuilderAccess::authorizeUsing(static fn (): bool => false);

        $this->actingAs($this->builderUser('no-builder@example.com'))
            ->getJson(route('voodbuilder.editor.blocks'))
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
}
