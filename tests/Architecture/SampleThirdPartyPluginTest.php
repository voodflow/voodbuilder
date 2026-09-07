<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Architecture;

use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionHooks;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Tests\Fixtures\SamplePlugin\SampleAcmePlugin;
use Voodflow\Voodbuilder\Tests\TestCase;

class SampleThirdPartyPluginTest extends TestCase
{
    public function test_sample_plugin_registers_block_binding_and_condition_without_core_edits(): void
    {
        SampleAcmePlugin::register();

        $blockIds = collect(app(EditorBlockRegistry::class)->toEditorBlocks())
            ->pluck('id')
            ->all();

        $this->assertContains('acme-hello', $blockIds);

        $bindings = app(BindingRegistry::class);
        $this->assertTrue($bindings->has('acme.site'));
        $this->assertSame('Acme tagline', $bindings->resolve('acme.site.tagline', new BindingContext));

        $this->assertTrue(
            EditorConditionHooks::evaluate('acme_feature_flag', ['value' => 'on'], null) === true,
        );
        $this->assertTrue(
            EditorConditionHooks::evaluate('acme_feature_flag', ['value' => 'off'], null) === false,
        );
    }
}
