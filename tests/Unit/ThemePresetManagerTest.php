<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\ThemePresetManager;
use Voodflow\Voodbuilder\Tests\TestCase;

class ThemePresetManagerTest extends TestCase
{
    public function test_it_loads_bundled_presets(): void
    {
        $presets = ThemePresetManager::bundled();

        $this->assertTrue($presets->contains(fn ($preset): bool => $preset->id === 'cosmolab-public'));
        $this->assertTrue($presets->contains(fn ($preset): bool => $preset->id === 'documentation-only'));
    }

    public function test_apply_preset_updates_site_settings(): void
    {
        $preset = ThemePresetManager::find('documentation-only');

        $this->assertNotNull($preset);

        $preset->apply();

        $this->assertSame('docs', VoodbuilderSettings::get('sub_theme'));
        $this->assertSame('documentation-only', VoodbuilderSettings::get('active_theme_preset_id'));
        $this->assertSame('docs', VoodbuilderSettings::get('content_channel_sub_themes')['docs'] ?? null);
    }

    public function test_export_and_import_roundtrip(): void
    {
        VoodbuilderSettings::saveData([
            'sub_theme' => 'site',
            'content_channel_sub_themes' => [
                'docs' => 'docs',
            ],
            'sub_theme_colors' => [],
        ]);

        $snapshot = ThemePresetManager::snapshotFromSettings('roundtrip', 'Roundtrip preset');
        $path = sys_get_temp_dir().'/voodbuilder-theme-roundtrip.json';

        ThemePresetManager::exportToFile($snapshot, $path);

        VoodbuilderSettings::saveData([
            'sub_theme' => 'docs',
            'content_channel_sub_themes' => [],
        ]);

        ThemePresetManager::importFromFile($path, apply: true, saveCustom: true);

        $this->assertSame('site', VoodbuilderSettings::get('sub_theme'));
        $this->assertSame('docs', VoodbuilderSettings::get('content_channel_sub_themes')['docs'] ?? null);
        $this->assertNotNull(ThemePresetManager::find('roundtrip'));
    }
}
