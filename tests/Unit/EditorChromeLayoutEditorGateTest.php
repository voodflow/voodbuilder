<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\Editor\EditorChromeLayoutEditorGate;
use Voodflow\Voodbuilder\Support\Editor\EditorCommunityBlockCatalog;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorChromeLayoutEditorGateTest extends TestCase
{
    public function test_layout_editor_config_hides_templates_and_limits_blocks_to_foundation(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Gate Test Layout',
            'slug' => 'gate-test-layout',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'css' => '',
            'js' => '',
            'enabled' => true,
        ]);

        $config = EditorChromeLayoutEditorGate::config($layout);

        $this->assertTrue($config['chromeLayoutMode']);
        $this->assertNull($config['pageTemplatesUrl']);
        $this->assertTrue($config['hideTemplates']);
        $this->assertSame([], $config['templateCategories']);
        $this->assertSame(
            EditorCommunityBlockCatalog::FOUNDATION_BLOCK_IDS,
            $config['blockAllowlist'],
        );
        $this->assertStringContainsString('chrome=1', (string) $config['blocksUrl']);
    }
}
