<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature\Companion;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Vpopups\Models\BuilderPopup;
use Voodflow\Vpopups\Support\EditorPopupEditorGate;
use Voodflow\Vpopups\Support\PopupEditorPreviewThemes;

class PopupEditorThemeAlignmentTest extends TestCase
{
    public function test_popup_editor_defaults_to_site_pages_preview_area(): void
    {
        if (! class_exists(EditorPopupEditorGate::class) || ! class_exists(BuilderPopup::class)) {
            $this->markTestSkipped('vpopups not available.');
        }

        config(['voodbuilder.popups.editor_sub_theme' => null]);

        $popup = BuilderPopup::query()->create([
            'name' => 'Theme check',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Hi</div>',
        ]);

        $config = EditorPopupEditorGate::config($popup);
        $expected = PopupEditorPreviewThemes::resolve();

        $this->assertSame(PopupEditorPreviewThemes::DEFAULT_AREA, $config['previewThemeArea']);
        $this->assertSame($expected['subTheme'], $config['subTheme']);
        $this->assertNotSame('', (string) ($config['themePaletteCss'] ?? ''));
        $this->assertNotEmpty($config['previewThemeOptions']);
        $this->assertArrayHasKey($expected['areaId'], $config['previewThemePalettes']);
    }

    public function test_popup_editor_honors_preview_theme_query(): void
    {
        if (! class_exists(EditorPopupEditorGate::class) || ! class_exists(BuilderPopup::class)) {
            $this->markTestSkipped('vpopups not available.');
        }

        config([
            'voodbuilder.popups.editor_sub_theme' => null,
            'voodbuilder.content_channel_sub_themes' => [],
        ]);

        app(ContentChannelRegistry::class)->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'docs';
            }

            public function label(): string
            {
                return 'Documentation';
            }

            public function routePatterns(): array
            {
                return ['vdocs.*'];
            }

            public function subTheme(): ?string
            {
                return null;
            }

            public function search(string $term, int $limit = 20): Collection
            {
                return collect();
            }
        });

        VoodbuilderSettings::saveData([
            ...VoodbuilderSettings::defaults(),
            'content_channel_sub_themes' => [
                'docs' => 'docs',
            ],
        ]);
        VoodbuilderSettings::clearCache();

        $this->app->instance('request', Request::create('/', 'GET', [
            PopupEditorPreviewThemes::QUERY_KEY => 'docs',
        ]));

        $popup = BuilderPopup::query()->create([
            'name' => 'Theme query',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Hi</div>',
        ]);

        $config = EditorPopupEditorGate::config($popup);

        $this->assertSame('docs', $config['previewThemeArea']);
        $this->assertSame('docs', $config['subTheme']);
    }
}
