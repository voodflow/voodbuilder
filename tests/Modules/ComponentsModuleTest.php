<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class ComponentsModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.components.enabled', false);
    }

    public function test_components_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(ComponentsModule::ID));
        $this->assertFalse(ComponentsModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.grapesjs.components.index'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.global-classes.index'));
    }

    public function test_components_route_is_absent_when_module_disabled(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'components-off@example.com',
        ])->save();

        $this->actingAs($user)
            ->getJson('/voodbuilder/grapesjs/components')
            ->assertNotFound();
    }
}
