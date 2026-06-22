<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Voodflow\Vpress\Support\ThemePalette;

class ThemePaletteTest extends TestCase
{
    #[Test]
    public function it_sanitizes_hex_colors(): void
    {
        $this->assertSame('#3451b2', ThemePalette::sanitizeColor('#3451b2'));
        $this->assertSame('#3451b2', ThemePalette::sanitizeColor('#3451B2'));
        $this->assertSame('#3451b2', ThemePalette::sanitizeColor('#35b'));
        $this->assertSame('#002b49', ThemePalette::sanitizeColor('002B49'));
        $this->assertSame('#ef7b00', ThemePalette::sanitizeColor('EF7B00'));
        $this->assertNull(ThemePalette::sanitizeColor('red'));
        $this->assertNull(ThemePalette::sanitizeColor(''));
    }

    #[Test]
    public function it_builds_css_for_custom_sub_theme_colors(): void
    {
        config()->set('vpress.sub_themes', [
            'default' => ['label' => 'Default'],
        ]);

        $css = ThemePalette::css();

        $this->assertStringContainsString('header[role=\'banner\']', $css);
        $this->assertStringContainsString('--vx-header-text', $css);

        $normalized = ThemePalette::normalize([
            'default' => [
                'custom' => true,
                'light' => [
                    'primary' => '#111111',
                    'secondary' => '#222222',
                ],
                'dark' => [
                    'primary' => '#aaaaaa',
                    'secondary' => '#bbbbbb',
                ],
            ],
        ]);

        $this->assertTrue($normalized['default']['custom']);
        $this->assertSame('#111111', $normalized['default']['light']['primary']);
    }

    #[Test]
    public function it_builds_css_for_semantic_theme_colors(): void
    {
        config()->set('vpress.sub_themes', [
            'site' => ['label' => 'Site'],
        ]);

        \Voodflow\Vpress\Models\VpressSettings::query()->create([
            'data' => array_merge(\Voodflow\Vpress\Models\VpressSettings::defaults(), [
                'sub_theme_colors' => [
                    'site' => [
                        'light' => [
                            'primary' => '#002b49',
                            'secondary' => '#ef7b00',
                            'header_bg' => '#002b49',
                            'header_text' => '#ffffff',
                            'body_bg' => '#ffffff',
                            'text' => '#1a1a1a',
                        ],
                    ],
                ],
            ]),
        ]);
        \Voodflow\Vpress\Models\VpressSettings::clearCache();

        $css = ThemePalette::css();

        $this->assertStringContainsString("--vx-header-bg:#002b49", $css);
        $this->assertStringContainsString("--color-vp-bg:#ffffff", $css);
        $this->assertStringContainsString("html[data-vpress-sub-theme='site']:not(.dark)", $css);
    }
}
