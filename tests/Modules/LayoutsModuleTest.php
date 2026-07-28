<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Modules\Layouts\LayoutsModule;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class LayoutsModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.layouts.enabled', false);
    }

    public function test_layouts_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(LayoutsModule::ID));
        $this->assertFalse(LayoutsModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.chrome-layouts.editor'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.chrome-layouts.content.update'));
    }

    public function test_chrome_layout_editor_route_is_absent_when_module_disabled(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'layouts-off@example.com',
        ])->save();

        $layout = ChromeLayout::query()->create([
            'name' => 'Default',
            'slug' => 'default',
            'enabled' => true,
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->get('/voodbuilder/chrome-layouts/'.$layout->getKey().'/editor')
            ->assertNotFound();
    }

    public function test_core_chrome_layout_resolver_still_respects_feature_flag_when_module_disabled(): void
    {
        config()->set('voodbuilder.chrome_layouts.enabled', true);

        $this->assertTrue(ChromeLayoutResolver::enabled());
    }
}
