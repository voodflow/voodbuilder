<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Voodflow\Voodbuilder\Support\LandingBlockContent;

class LandingBlockContentTest extends TestCase
{
    public function test_media_url_falls_back_to_legacy_url_field(): void
    {
        $url = LandingBlockContent::mediaUrl([
            'background_image_url' => 'https://example.com/hero.jpg',
        ], 'background_image');

        $this->assertSame('https://example.com/hero.jpg', $url);
    }

    public function test_background_image_url_supports_http_legacy_value(): void
    {
        $url = LandingBlockContent::backgroundImageUrl([
            'background_image_url' => 'https://cdn.test/bg.jpg',
        ]);

        $this->assertSame('https://cdn.test/bg.jpg', $url);
    }

    public function test_section_returns_unified_layout_keys(): void
    {
        $section = LandingBlockContent::section([
            'section_width' => 'bleed',
            'section_padding' => 'large',
            'background_tone' => 'dark',
        ], ['section_width' => 'contained']);

        $this->assertStringContainsString('vp-landing-section--bleed', $section['shell']);
        $this->assertArrayHasKey('appearance', $section);
        $this->assertTrue($section['onDark']);
    }
}
