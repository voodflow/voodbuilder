<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voodflow\Voodbuilder\Models\PageTemplate;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsPageTemplatesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        PageBuilderAccess::authorizeUsing(null);

        parent::tearDown();
    }

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
            Panel::make()
                ->id('admin')
                ->path('admin'),
        );

        PageBuilderAccess::authorizeUsing(
            static fn (): bool => auth()->check(),
        );
    }

    public function test_admin_can_store_and_list_page_templates(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $response = $this->postJson(route('voodbuilder.grapesjs.page-templates.store'), [
            'name' => 'Product landing',
            'category' => 'Hero',
            'html' => '<section>Hero</section>',
            'css' => '.hero { color: red; }',
            'js' => '',
        ]);

        $response->assertCreated()
            ->assertJsonPath('template.name', 'Product landing')
            ->assertJsonPath('template.category', 'Hero');

        $this->getJson(route('voodbuilder.grapesjs.page-templates.index'))
            ->assertOk()
            ->assertJsonCount(1, 'templates')
            ->assertJsonPath('templates.0.name', 'Product landing');
    }

    public function test_admin_can_delete_page_template(): void
    {
        $user = $this->adminUser();
        $template = PageTemplate::query()->create([
            'name' => 'Old landing',
            'category' => 'General',
            'html' => '<section>Old</section>',
        ]);

        $this->actingAs($user);

        $this->deleteJson(route('voodbuilder.grapesjs.page-templates.destroy', $template))
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('voodbuilder_page_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_guest_cannot_store_page_template(): void
    {
        $this->postJson(route('voodbuilder.grapesjs.page-templates.store'), [
            'name' => 'Guest template',
            'html' => '<section>Nope</section>',
        ])->assertUnauthorized();
    }

    public function test_rejects_invalid_import_url(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->postJson(route('voodbuilder.grapesjs.page-templates.import-url'), [
                'url' => 'http://insecure.example.com/template.json',
            ])
            ->assertUnprocessable();
    }

    public function test_catalog_returns_empty_when_not_configured(): void
    {
        config(['voodbuilder.page_templates.catalog_url' => null]);

        $user = $this->adminUser();

        $this->actingAs($user)
            ->getJson(route('voodbuilder.grapesjs.page-templates.catalog'))
            ->assertOk()
            ->assertJsonPath('templates', []);
    }

    private function adminUser(): User
    {
        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }
        };

        $user->forceFill([
            'name' => 'Admin',
            'email' => 'admin-templates@example.com',
        ])->save();

        return $user;
    }
}
