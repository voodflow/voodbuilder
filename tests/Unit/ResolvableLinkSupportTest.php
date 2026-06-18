<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Voodflow\Vpress\Support\LandingBlockSupport;
use Voodflow\Vpress\Support\ResolvableLinkSupport;

class ResolvableLinkSupportTest extends TestCase
{
    public function test_resolves_mail_link_from_new_format(): void
    {
        $url = ResolvableLinkSupport::resolve([
            'button_link_type' => 'mail',
            'button_link' => 'info@soundmit.com',
        ], 'button');

        $this->assertSame('mailto:info@soundmit.com', $url);
    }

    public function test_resolves_legacy_mailto_url(): void
    {
        $url = ResolvableLinkSupport::resolve([
            'button_url' => 'mailto:info@soundmit.com',
        ], 'button', 'button_url');

        $this->assertSame('mailto:info@soundmit.com', $url);
    }

    public function test_resolves_legacy_plain_url(): void
    {
        $url = ResolvableLinkSupport::resolve([
            'primary_button_url' => 'https://example.com',
        ], 'primary_button', 'primary_button_url');

        $this->assertSame('https://example.com', $url);
    }

    public function test_bleed_sections_have_no_rounded_corners(): void
    {
        $class = LandingBlockSupport::sectionCornerClass([
            'section_width' => 'bleed',
        ]);

        $this->assertSame('', $class);
    }

    public function test_contained_sections_keep_rounded_corners(): void
    {
        $class = LandingBlockSupport::sectionCornerClass([
            'section_width' => 'contained',
        ]);

        $this->assertSame('rounded-2xl', $class);
    }

    public function test_custom_background_uses_color_picker_value(): void
    {
        $appearance = LandingBlockSupport::sectionAppearance([
            'background_tone' => 'custom',
            'background_color' => '#000000',
        ]);

        $this->assertStringContainsString('background-color: #000000', $appearance['style']);
        $this->assertStringContainsString('text-white', $appearance['class']);
    }

    public function test_custom_light_background_uses_dark_text(): void
    {
        $appearance = LandingBlockSupport::sectionAppearance([
            'background_tone' => 'custom',
            'background_color' => '#ffffff',
        ]);

        $this->assertStringContainsString('text-vp-text-1', $appearance['class']);
        $this->assertTrue(LandingBlockSupport::onDarkBackground([
            'background_tone' => 'custom',
            'background_color' => '#ffffff',
        ]) === false);
    }
}
