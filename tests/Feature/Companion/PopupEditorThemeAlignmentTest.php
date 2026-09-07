<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature\Companion;

use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Vpopups\Models\BuilderPopup;
use Voodflow\Vpopups\Support\EditorPopupEditorGate;

class PopupEditorThemeAlignmentTest extends TestCase
{
    public function test_popup_editor_defaults_to_site_pages_channel_theme(): void
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

        $this->assertSame(EditorGate::chromeShellPreviewSubTheme(), $config['subTheme']);
        $this->assertNotSame('', (string) ($config['themePaletteCss'] ?? ''));
    }
}
