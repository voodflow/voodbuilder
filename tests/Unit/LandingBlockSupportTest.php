<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\LandingBlockSupport;
use Voodflow\Vpress\Tests\TestCase;

class LandingBlockSupportTest extends TestCase
{
    public function test_section_shell_class_includes_width_and_padding(): void
    {
        $class = LandingBlockSupport::sectionShellClass([
            'section_width' => 'bleed',
            'section_padding' => 'large',
        ]);

        $this->assertStringContainsString('vp-landing-section--bleed', $class);
        $this->assertStringContainsString('vp-landing-section--padding-large', $class);
    }

    public function test_youtube_embed_normalizes_watch_url(): void
    {
        $url = \Voodflow\Vpress\Support\YoutubeEmbed::normalize('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0', $url);
    }
}
