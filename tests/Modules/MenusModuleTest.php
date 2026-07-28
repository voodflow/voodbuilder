<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Modules\Menus\MenusModule;
use Voodflow\Voodbuilder\Support\Navigation;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class MenusModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.menus.enabled', false);
    }

    public function test_menus_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(MenusModule::ID));
        $this->assertFalse(MenusModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.admin.navigation-menus.preview'));
    }

    public function test_menu_preview_route_is_absent_when_module_disabled(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'menus-off@example.com',
        ])->save();

        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
        ]);

        $this->actingAs($user)
            ->get('/voodbuilder/admin/navigation-menus/'.$menu->getKey().'/preview')
            ->assertNotFound();
    }

    public function test_core_navigation_resolution_still_works_when_menus_module_disabled(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => '/',
            'sort_order' => 0,
        ]);

        $this->assertCount(1, Navigation::items('main'));
    }
}
