<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;
use Voodflow\Voodbuilder\Modules\DynamicData\DynamicDataModule;
use Voodflow\Voodbuilder\Modules\Popups\PopupsModule;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Support\GrapesJs\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class CommercialBoundaryTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.license.edition', 'community');
        $app['config']->set('voodbuilder.license.cache', false);
    }

    public function test_components_unlocks_with_companion_plugin_even_on_community(): void
    {
        // TestCase activates VoodbuilderComponents like a host Filament panel would.
        // The paid package (plugin registration) is the commercial gate — not Agency edition alone.
        $this->assertSame('community', Voodbuilder::entitlements()->edition());
        $this->assertFalse(Voodbuilder::can('components.library'));
        $this->assertTrue(ComponentsModule::isEnabled());
        $this->assertTrue(Route::has('voodbuilder.grapesjs.components.index'));
    }

    public function test_community_keeps_core_cms_modules(): void
    {
        $this->assertTrue(TemplatesModule::isEnabled());
        $this->assertTrue(PopupsModule::isEnabled());
        $this->assertTrue(Route::has('voodbuilder.grapesjs.page-templates.index'));
        $this->assertTrue(Route::has('voodbuilder.popups.public'));
    }

    public function test_dynamic_data_requires_companion_plugin_and_is_active_in_testbench(): void
    {
        // TestCase activates VoodbuilderDynamicData like a host Filament panel would.
        $this->assertTrue(DynamicDataModule::isEnabled());
        $this->assertTrue(Route::has('voodbuilder.grapesjs.bindings'));
        // List repeat remains Pro/Agency even when the plugin is installed.
        $this->assertFalse(Voodbuilder::can('dynamic-data.collections'));
        $this->assertFalse(DynamicDataCollectionsBridge::moduleEnabled());
    }

    public function test_community_edition_matrix_excludes_dynamic_data_capabilities(): void
    {
        $this->assertFalse(Voodbuilder::can('dynamic-data.single'));
        $this->assertFalse(Voodbuilder::can('dynamic-data.collections'));
    }

    public function test_community_hides_pro_template_import_and_agency_export_routes(): void
    {
        $this->assertFalse(Route::has('voodbuilder.grapesjs.page-templates.import'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.page-templates.catalog'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.page-templates.export'));
    }
}
