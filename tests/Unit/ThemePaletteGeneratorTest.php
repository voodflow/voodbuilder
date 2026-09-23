<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\ThemePaletteGenerator;
use Voodflow\Voodbuilder\Tests\TestCase;

class ThemePaletteGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_light_and_dark_palettes_from_accent_seed(): void
    {
        $palette = ThemePaletteGenerator::fromSeeds('#c8102e');

        $this->assertSame('#c8102e', $palette['light']['primary']);
        $this->assertSame('#0f172a', $palette['light']['header_bg']);
        $this->assertSame('#ffffff', $palette['light']['header_text']);
        $this->assertSame('#f8fafc', $palette['light']['footer_bg']);
        $this->assertSame('#ffffff', $palette['light']['body_bg']);
        $this->assertSame('#111827', $palette['light']['text']);
        $this->assertSame('#f8fafc', $palette['dark']['text']);
        $this->assertArrayHasKey('footer_bg', $palette['dark']);
        $this->assertNotSame($palette['light']['primary'], $palette['dark']['primary']);
    }

    #[Test]
    public function it_honors_optional_secondary_and_header_seeds(): void
    {
        $palette = ThemePaletteGenerator::fromSeeds('#c8102e', '#e86a17', '#002b49');

        $this->assertSame('#e86a17', $palette['light']['secondary']);
        $this->assertSame('#002b49', $palette['light']['header_bg']);
        $this->assertSame('#ffffff', $palette['light']['header_text']);
    }

    #[Test]
    public function it_builds_dark_palette_from_existing_light_values(): void
    {
        $dark = ThemePaletteGenerator::darkFromLight([
            'primary' => '#3451b2',
            'secondary' => '#5672cd',
            'header_bg' => '#0f172a',
            'header_text' => '#ffffff',
            'body_bg' => '#ffffff',
            'text' => '#111827',
        ]);

        $this->assertSame('#f8fafc', $dark['text']);
        $this->assertSame('#ffffff', $dark['header_text']);
        $this->assertNotSame('#3451b2', $dark['primary']);
    }

    #[Test]
    public function it_builds_light_palette_from_existing_dark_values(): void
    {
        $light = ThemePaletteGenerator::lightFromDark([
            'primary' => '#a8b1ff',
            'secondary' => '#c4caff',
            'header_bg' => '#020617',
            'header_text' => '#ffffff',
            'body_bg' => '#151034',
            'footer_bg' => '#2b3343',
            'text' => '#f8fafc',
        ]);

        $this->assertSame('#111827', $light['text']);
        $this->assertSame('#ffffff', $light['body_bg']);
        $this->assertSame('#f8fafc', $light['footer_bg']);
        $this->assertNotSame('#a8b1ff', $light['primary']);
        $this->assertArrayHasKey('header_text', $light);
    }

    #[Test]
    public function it_generates_a_single_mode_without_returning_the_other(): void
    {
        $lightOnly = ThemePaletteGenerator::fromSeedsForMode('light', '#c8102e');
        $darkOnly = ThemePaletteGenerator::fromSeedsForMode('dark', '#c8102e');

        $this->assertSame('#c8102e', $lightOnly['primary']);
        $this->assertSame('#ffffff', $lightOnly['body_bg']);
        $this->assertSame('#f8fafc', $darkOnly['text']);
        $this->assertArrayNotHasKey('light', $lightOnly);
        $this->assertArrayNotHasKey('dark', $darkOnly);
    }
}
