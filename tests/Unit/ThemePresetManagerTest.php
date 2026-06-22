<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ThemePresetManager;
use Voodflow\Vpress\Tests\TestCase;

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

        $this->assertSame('default', VpressSettings::get('sub_theme'));
        $this->assertSame('documentation-only', VpressSettings::get('active_theme_preset_id'));
        $this->assertSame('default', VpressSettings::get('content_channel_sub_themes')['docs'] ?? null);
    }

    public function test_export_and_import_roundtrip(): void
    {
        VpressSettings::saveData([
            'sub_theme' => 'site',
            'content_channel_sub_themes' => [
                'docs' => 'default',
            ],
            'sub_theme_colors' => [],
        ]);

        $snapshot = ThemePresetManager::snapshotFromSettings('roundtrip', 'Roundtrip preset');
        $path = sys_get_temp_dir().'/vpress-theme-roundtrip.json';

        ThemePresetManager::exportToFile($snapshot, $path);

        VpressSettings::saveData([
            'sub_theme' => 'default',
            'content_channel_sub_themes' => [],
        ]);

        ThemePresetManager::importFromFile($path, apply: true, saveCustom: true);

        $this->assertSame('site', VpressSettings::get('sub_theme'));
        $this->assertSame('default', VpressSettings::get('content_channel_sub_themes')['docs'] ?? null);
        $this->assertNotNull(ThemePresetManager::find('roundtrip'));
    }
}
