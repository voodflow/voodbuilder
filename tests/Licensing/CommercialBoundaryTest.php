<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Licensing;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Modules\Components\ComponentsModule;
use Voodflow\Voodbuilder\Modules\DynamicData\DynamicDataModule;
use Voodflow\Voodbuilder\Modules\Popups\PopupsModule;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;
use Voodflow\Voodbuilder\Support\Editor\TemplateAuthoringBridge;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;
use Voodflow\VoodbuilderTemplates\VoodbuilderTemplates;

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
        if (! class_exists(ComponentsModule::class)) {
            $this->markTestSkipped('voodbuilder-components companion package is not available.');
        }

        $this->assertSame('community', Voodbuilder::entitlements()->edition());
        $this->assertFalse(Voodbuilder::can('components.library'));
        $this->assertTrue(ComponentsModule::isEnabled());
        $this->assertTrue(Route::has('voodbuilder.editor.components.index'));
    }

    public function test_community_keeps_core_cms_modules_and_marketplace_template_url(): void
    {
        $this->assertTrue(TemplatesModule::isEnabled());

        if (class_exists(PopupsModule::class)) {
            $this->assertTrue(PopupsModule::isEnabled());
            $this->assertTrue(Route::has('voodbuilder.popups.public'));
        }

        $this->assertTrue(Route::has('voodbuilder.editor.page-templates.index'));
        $this->assertTrue(Route::has('voodbuilder.editor.page-templates.import-url'));
    }

    public function test_dynamic_data_requires_companion_plugin_and_is_active_in_testbench(): void
    {
        if (! class_exists(DynamicDataModule::class)) {
            $this->markTestSkipped('voodbuilder-dynamic-data companion package is not available.');
        }

        $this->assertTrue(DynamicDataModule::isEnabled());
        $this->assertTrue(Route::has('voodbuilder.editor.bindings'));
        $this->assertFalse(Voodbuilder::can('dynamic-data.collections'));
        $this->assertFalse(DynamicDataCollectionsBridge::moduleEnabled());
    }

    public function test_community_edition_matrix_excludes_dynamic_data_capabilities(): void
    {
        $this->assertFalse(Voodbuilder::can('dynamic-data.single'));
        $this->assertFalse(Voodbuilder::can('dynamic-data.collections'));
    }

    public function test_community_without_templates_plugin_blocks_authoring_bridge(): void
    {
        if (class_exists(VoodbuilderTemplates::class)) {
            VoodbuilderTemplates::reset();
        }

        // Advanced JSON import/export require activation of the companion plugin
        // (package presence alone is not enough).
        $this->assertFalse(TemplateAuthoringBridge::canImportJson());
        $this->assertFalse(TemplateAuthoringBridge::canExport());
        $this->assertFalse(TemplateAuthoringBridge::isEnabled());

        // In the monorepo the companion class is path-autoloaded; on a clean
        // community install without the Composer package, pluginInstalled is false.
        if (! class_exists(VoodbuilderTemplates::class)) {
            $this->assertFalse(TemplateAuthoringBridge::pluginInstalled());
        }
    }

    public function test_templates_plugin_unlocks_authoring_on_community(): void
    {
        if (! class_exists(VoodbuilderTemplates::class)) {
            $this->markTestSkipped('voodbuilder-templates companion package is not available.');
        }

        VoodbuilderTemplates::reset();
        VoodbuilderTemplates::activate();

        $this->assertTrue(TemplateAuthoringBridge::isEnabled());
        $this->assertTrue(TemplateAuthoringBridge::canImportJson());
        $this->assertTrue(TemplateAuthoringBridge::canExport());
    }
}
