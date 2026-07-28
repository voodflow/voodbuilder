<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Voodflow\Voodbuilder\Modules\Themes\ThemesModule;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class ThemesModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.themes.enabled', false);
    }

    public function test_themes_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(ThemesModule::ID));
        $this->assertFalse(ThemesModule::isEnabled());
    }

    public function test_theme_studio_livewire_alias_is_absent_when_disabled(): void
    {
        $this->expectException(\Livewire\Exceptions\ComponentNotFoundException::class);

        app('livewire')->new('voodbuilder.themes-workspace');
    }

    public function test_core_sub_theme_registry_still_boots_when_themes_module_disabled(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertNotEmpty($registry->ids());
    }
}
