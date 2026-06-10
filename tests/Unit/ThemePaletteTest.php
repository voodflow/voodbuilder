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

        $this->assertSame('', $css);

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
}
