<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Modules\Popups\PopupsModule;
use Voodflow\Voodbuilder\Support\Popups\PopupsOrphanStatus;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class PopupsModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.popups.enabled', false);
    }

    public function test_popups_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(PopupsModule::ID));
        $this->assertFalse(PopupsModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.grapesjs.popups.index'));
        $this->assertFalse(Route::has('voodbuilder.popups.public'));
        $this->assertFalse(Route::has('voodbuilder.popups.editor'));
    }

    public function test_popup_routes_are_absent_when_module_disabled(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'popups-off@example.com',
        ])->save();

        $this->actingAs($user)
            ->getJson('/voodbuilder/grapesjs/popups')
            ->assertNotFound();

        $this->getJson('/voodbuilder/popups/data')
            ->assertNotFound();
    }

    public function test_orphaned_popup_data_is_detected_when_module_disabled(): void
    {
        BuilderPopup::query()->create([
            'name' => 'Orphan',
            'enabled' => true,
            'paused' => false,
            'priority' => 0,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<p>Hi</p>',
        ]);

        $this->assertTrue(PopupsOrphanStatus::detected());
        $this->assertSame(1, PopupsOrphanStatus::count());
        $this->assertNotSame('', PopupsOrphanStatus::adminBody());
    }

    public function test_public_boot_partial_renders_empty_when_module_disabled(): void
    {
        BuilderPopup::query()->create([
            'name' => 'Still in DB',
            'enabled' => true,
            'paused' => false,
            'priority' => 0,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<p>Hi</p>',
        ]);

        $html = view('voodbuilder::components.popups-boot')->render();

        $this->assertStringNotContainsString('data-voodbuilder-popups-config', $html);
    }
}
