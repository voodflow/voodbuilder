<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorGateTest extends TestCase
{
    protected function tearDown(): void
    {
        EditorGate::authorizeUsing(null);

        parent::tearDown();
    }

    public function test_admin_preview_does_not_enable_editor_without_edit_query(): void
    {
        EditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesEditorBuilder(),
        );

        $page = new SitePage([
            'builder' => PageBuilder::Visual,
        ]);

        $this->app->instance('request', Request::create('/pages/landing-page', 'GET'));

        $this->assertTrue(EditorGate::canEdit($page));
        $this->assertFalse(EditorGate::isEditing($page));
    }

    public function test_edit_query_enables_editor_for_authorized_users(): void
    {
        EditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesEditorBuilder(),
        );

        $page = SitePage::query()->create([
            'title' => 'Landing',
            'slug' => 'landing-gate-test',
            'builder' => PageBuilder::Visual,
            'published' => true,
            'builder_payload' => [
                'html' => '<section>Hero</section>',
                'css' => '',
            ],
        ]);

        $this->app->instance('request', Request::create('/pages/landing-page?edit=1', 'GET'));

        $this->assertTrue(EditorGate::isEditing($page));

        $config = EditorGate::config($page);

        $this->assertSame('<section class="voodbuilder-editor-section bg-vp-bg">Hero</section>', $config['initial']['html']);
        $this->assertArrayHasKey('pageManager', $config['initial']);
        $this->assertArrayHasKey('blocksUrl', $config);
        $this->assertArrayNotHasKey('blocks', $config);
        $this->assertSame('Landing', $config['pageTitle']);
        $this->assertSame('Page', $config['labels']['editingContextPage']);
        $this->assertSame('Layout', $config['labels']['editingContextLayout']);
        $this->assertSame('Popup', $config['labels']['editingContextPopup']);
        $this->assertTrue($config['imageEditor']);
        $this->assertArrayHasKey('editImage', $config['labels']);
        $this->assertArrayHasKey('imageEditorTitle', $config['labels']);
        $this->assertArrayHasKey('imageEditorPlaceholderHint', $config['labels']);
        $this->assertArrayHasKey('imageSettingsHeroTitle', $config['labels']);
        $this->assertArrayHasKey('imageSettingsSrc', $config['labels']);
        $this->assertSame('/voodbuilder/editor/pages/'.$page->getKey(), $config['saveUrl']);
        $this->assertIsString($config['uploadUrl']);
    }

    public function test_config_exposes_save_url_and_tolerates_optional_media_routes(): void
    {
        EditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesEditorBuilder(),
        );

        $page = SitePage::query()->create([
            'title' => 'Landing',
            'slug' => 'landing-gate-save-url',
            'builder' => PageBuilder::Visual,
            'published' => true,
            'builder_payload' => [
                'html' => '<section>Hero</section>',
                'css' => '',
            ],
        ]);

        $this->app->instance('request', Request::create('/pages/landing-page?edit=1', 'GET'));

        $config = EditorGate::config($page);

        $this->assertSame('/voodbuilder/editor/pages/'.$page->getKey(), $config['saveUrl']);
        $this->assertIsString($config['uploadUrl']);

        if (
            Route::has('vmedia.media.upload')
            || Route::has('voodbuilder.editor.upload')
        ) {
            $this->assertNotSame('', $config['uploadUrl']);
        } else {
            // Companion inactive and core fallback not registered — editor must still boot.
            $this->assertSame('', $config['uploadUrl']);
        }
    }

    public function test_config_prefers_vmedia_upload_when_registered_else_core_fallback(): void
    {
        EditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesEditorBuilder(),
        );

        $page = SitePage::query()->create([
            'title' => 'Landing',
            'slug' => 'landing-gate-upload-preference',
            'builder' => PageBuilder::Visual,
            'published' => true,
            'builder_payload' => [
                'html' => '<section>Hero</section>',
                'css' => '',
            ],
        ]);

        $this->app->instance('request', Request::create('/pages/landing-page?edit=1', 'GET'));

        $config = EditorGate::config($page);
        $uploadUrl = (string) ($config['uploadUrl'] ?? '');

        if (Route::has('vmedia.media.upload')) {
            $this->assertSame(
                route('vmedia.media.upload', absolute: false),
                $uploadUrl,
            );
        } elseif (Route::has('voodbuilder.editor.upload')) {
            $this->assertSame(
                route('voodbuilder.editor.upload', absolute: false),
                $uploadUrl,
            );
        } else {
            $this->assertSame('', $uploadUrl);
        }

        // Missing companion must never throw while resolving editor config.
        $this->assertIsArray($config);
        $this->assertArrayHasKey('saveUrl', $config);
    }

    public function test_image_editor_can_be_disabled_via_config(): void
    {
        EditorGate::authorizeUsing(
            static fn (SitePage $page): bool => $page->usesEditorBuilder(),
        );

        config(['voodbuilder.editor.image_editor' => false]);

        $page = SitePage::query()->create([
            'title' => 'Landing',
            'slug' => 'landing-gate-image-editor-off',
            'builder' => PageBuilder::Visual,
            'published' => true,
            'builder_payload' => [
                'html' => '<section>Hero</section>',
                'css' => '',
            ],
        ]);

        $this->app->instance('request', Request::create('/pages/landing-page?edit=1', 'GET'));

        $config = EditorGate::config($page);

        $this->assertFalse($config['imageEditor']);
    }
}
