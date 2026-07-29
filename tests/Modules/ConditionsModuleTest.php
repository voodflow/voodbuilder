<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\Conditions\ConditionsModule;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorElementConditionRenderer;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class ConditionsModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.conditions.enabled', false);
    }

    public function test_conditions_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(ConditionsModule::ID));
        $this->assertFalse(Voodbuilder::modules()->isEnabled(ConditionsModule::ID));
        $this->assertFalse(ConditionsModule::isEnabled());
    }

    public function test_disabled_conditions_keep_all_blocks_visible(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__).'/Fixtures/0.0.11/sample-page.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        app()->setLocale('it');

        $page = new SitePage([
            'title' => 'Conditions off',
            'slug' => 'conditions-off',
            'locale' => 'it',
            'builder' => PageBuilder::Visual,
        ]);

        $html = app(EditorElementConditionRenderer::class)->render(
            (string) $fixture['condition_html'],
            $page,
        );

        $this->assertStringContainsString('Visible in EN', $html);
        $this->assertStringContainsString('Visible in IT', $html);
        $this->assertStringNotContainsString('data-voodbuilder-conditions', $html);
    }
}
