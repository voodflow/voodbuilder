<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Modules\DynamicData\DynamicDataModule;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class DynamicDataModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.dynamic_data.enabled', false);
    }

    public function test_dynamic_data_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(DynamicDataModule::ID));
        $this->assertFalse(DynamicDataModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.grapesjs.bindings'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.bindings.preview'));
    }

    public function test_bindings_route_is_absent_when_module_disabled(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'dynamic-data-off@example.com',
        ])->save();

        $this->actingAs($user)
            ->getJson('/voodbuilder/grapesjs/bindings')
            ->assertNotFound();
    }

    public function test_core_binding_registry_remains_bound_when_module_disabled(): void
    {
        $this->assertTrue($this->app->bound(BindingRegistry::class));
        $this->assertInstanceOf(BindingRegistry::class, app(BindingRegistry::class));
    }
}