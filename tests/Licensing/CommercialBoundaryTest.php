<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;
use Voodflow\Voodbuilder\Modules\DynamicData\DynamicDataModule;
use Voodflow\Voodbuilder\Modules\Popups\PopupsModule;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
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

    public function test_community_boots_without_agency_components_module(): void
    {
        $this->assertSame('community', Voodbuilder::entitlements()->edition());
        $this->assertTrue(Voodbuilder::modules()->has(ComponentsModule::ID));
        $this->assertFalse(ComponentsModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.grapesjs.components.index'));
    }

    public function test_community_keeps_core_cms_modules(): void
    {
        $this->assertTrue(TemplatesModule::isEnabled());
        $this->assertTrue(DynamicDataModule::isEnabled());
        $this->assertTrue(PopupsModule::isEnabled());
        $this->assertTrue(Route::has('voodbuilder.grapesjs.page-templates.index'));
        $this->assertTrue(Route::has('voodbuilder.grapesjs.bindings'));
        $this->assertTrue(Route::has('voodbuilder.popups.public'));
    }

    public function test_community_hides_pro_template_import_and_agency_export_routes(): void
    {
        $this->assertFalse(Route::has('voodbuilder.grapesjs.page-templates.import'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.page-templates.catalog'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.page-templates.export'));
    }
}
