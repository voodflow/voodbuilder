<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\SubThemeLocator;
use Voodflow\Vpress\Support\ThemePalette;
use Voodflow\Vpress\Tests\TestCase;

class ThemePaletteResetTest extends TestCase
{
    public function test_it_resets_custom_colors_for_a_theme(): void
    {
        config()->set('vpress.sub_themes', [
            'default' => ['label' => 'Documentation'],
        ]);

        VpressSettings::query()->create([
            'data' => array_merge(VpressSettings::defaults(), [
                'sub_theme_colors' => [
                    'default' => [
                        'light' => [
                            'primary' => '#111111',
                            'text' => '#ffffff',
                        ],
                    ],
                ],
            ]),
        ]);
        VpressSettings::clearCache();

        $this->assertTrue(ThemePalette::themeHasCustomColors('default'));

        ThemePalette::resetForTheme('default');

        $this->assertFalse(ThemePalette::themeHasCustomColors('default'));
        $this->assertStringNotContainsString('--color-vp-text-1:#ffffff', ThemePalette::css());
    }

    public function test_it_labels_bundled_themes_without_a_separate_css_file(): void
    {
        $this->assertSame('package', SubThemeLocator::originFor('default'));
    }
}
