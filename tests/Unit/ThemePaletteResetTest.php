<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SubThemeLocator;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Tests\TestCase;

class ThemePaletteResetTest extends TestCase
{
    public function test_it_resets_custom_colors_for_a_theme(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'docs' => ['label' => 'Documentation'],
        ]);

        VoodbuilderSettings::query()->create([
            'data' => array_merge(VoodbuilderSettings::docss(), [
                'sub_theme_colors' => [
                    'docs' => [
                        'light' => [
                            'primary' => '#111111',
                            'text' => '#ffffff',
                        ],
                    ],
                ],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $this->assertTrue(ThemePalette::themeHasCustomColors('docs'));

        ThemePalette::resetForTheme('docs');

        $this->assertFalse(ThemePalette::themeHasCustomColors('docs'));
        $this->assertStringNotContainsString('--color-vp-text-1:#ffffff', ThemePalette::css());
    }

    public function test_it_labels_bundled_themes_without_a_separate_css_file(): void
    {
        $this->assertSame('package', SubThemeLocator::originFor('docs'));
    }
}
