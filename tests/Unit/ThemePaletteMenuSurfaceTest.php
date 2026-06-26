<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ThemePalette;
use Voodflow\Vpress\Tests\TestCase;

class ThemePaletteMenuSurfaceTest extends TestCase
{
    public function test_it_preserves_readable_menu_text_when_body_text_is_light(): void
    {
        config()->set('vpress.sub_themes', [
            'docs' => ['label' => 'Documentation'],
        ]);

        VpressSettings::query()->create([
            'data' => array_merge(VpressSettings::docss(), [
                'sub_theme_colors' => [
                    'docs' => [
                        'light' => [
                            'text' => '#ffffff',
                            'body_bg' => '#d9d9d9',
                        ],
                    ],
                ],
            ]),
        ]);
        VpressSettings::clearCache();

        $css = ThemePalette::css();

        $this->assertStringContainsString('--vx-menu-text:#3c3c43', $css);
        $this->assertStringContainsString("header[role='banner'] [role='menu']", $css);
        $this->assertStringContainsString('--color-vp-text-1:#ffffff', $css);
    }
}
