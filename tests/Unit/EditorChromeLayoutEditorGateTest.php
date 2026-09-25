<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Route;
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
            EditorCommunityBlockCatalog::expandIdsForBlockManager(
                EditorCommunityBlockCatalog::FOUNDATION_BLOCK_IDS,
            ),
            $config['blockAllowlist'],
        );
        $this->assertStringContainsString('chrome=1', (string) $config['blocksUrl']);
        $this->assertArrayHasKey('mediaLibraryUrl', $config);
        $this->assertArrayHasKey('mediaGalleriesUrl', $config);
        $this->assertArrayHasKey('mediaReplaceUrl', $config);
    }

    public function test_layout_editor_config_wires_vmedia_browser_when_routes_exist(): void
    {
        if (! Route::has('vmedia.media.index')
            || ! Route::has('vmedia.media.galleries')) {
            $this->markTestSkipped('vmedia media routes are not registered in this testbench boot.');
        }

        $layout = ChromeLayout::query()->create([
            'name' => 'Media Gate Layout',
            'slug' => 'media-gate-layout',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'css' => '',
            'js' => '',
            'enabled' => true,
        ]);

        $config = EditorChromeLayoutEditorGate::config($layout);

        $this->assertNotEmpty($config['mediaLibraryUrl']);
        $this->assertNotEmpty($config['mediaGalleriesUrl']);
    }
}
