<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Vpress\Support\ThemePaletteGenerator;
use Voodflow\Vpress\Tests\TestCase;

class ThemePaletteGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_light_and_dark_palettes_from_accent_seed(): void
    {
        $palette = ThemePaletteGenerator::fromSeeds('#c8102e');

        $this->assertSame('#c8102e', $palette['light']['primary']);
        $this->assertSame('#0f172a', $palette['light']['header_bg']);
        $this->assertSame('#ffffff', $palette['light']['header_text']);
        $this->assertSame('#ffffff', $palette['light']['body_bg']);
        $this->assertSame('#111827', $palette['light']['text']);
        $this->assertSame('#f8fafc', $palette['dark']['text']);
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
}
