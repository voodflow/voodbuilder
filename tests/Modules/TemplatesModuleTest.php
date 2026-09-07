<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class TemplatesModuleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.templates.enabled', false);
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

    protected function tearDown(): void
    {
        PageBuilderAccess::authorizeUsing(null);

        parent::tearDown();
    }

    public function test_templates_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(TemplatesModule::ID));
        $this->assertFalse(TemplatesModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.editor.page-templates.index'));
        $this->assertFalse(Route::has('voodbuilder.editor.page-templates.store'));
    }

    public function test_template_routes_are_absent_when_module_disabled(): void
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
            'email' => 'templates-off@example.com',
        ])->save();

        $this->actingAs($user);

        $this->getJson('/voodbuilder/editor/page-templates')->assertNotFound();
    }
}
