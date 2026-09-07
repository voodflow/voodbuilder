<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\Editor\EditorHeroBackgroundNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorHeroBackgroundNormalizerTest extends TestCase
{
    #[Test]
    public function it_hardens_youtube_hero_iframe_and_strips_letterbox_classes(): void
    {
        $html = <<<'HTML'
<section class="voodbuilder-editor-section" data-voodbuilder-section-block="vb-bg-video">
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media">
    <div class="voodbuilder-hero-media__embed" data-vb-embed-bg>
      <iframe
        class="voodbuilder-hero-media__iframe w-full aspect-video rounded vb-video-host"
        style="width:100%;max-width:100%;height:auto;"
        loading="lazy"
        src="https://www.youtube-nocookie.com/embed/UJ4Ixv2qQHY?autoplay=1&controls=0&loop=1&playlist=UJ4Ixv2qQHY"
      ></iframe>
    </div>
  </div>
</section>
HTML;

        $normalized = EditorHeroBackgroundNormalizer::normalize($html);

        $this->assertStringContainsString('mute=1', $normalized);
        $this->assertStringContainsString('controls=0', $normalized);
        $this->assertStringContainsString('loading="eager"', $normalized);
        $this->assertStringContainsString('voodbuilder-hero-media__iframe', $normalized);
        $this->assertStringNotContainsString('vb-video-host', $normalized);
        $this->assertStringNotContainsString('aspect-video', $normalized);
        $this->assertStringNotContainsString('style="width:100%', $normalized);
    }

    #[Test]
    public function it_hardens_vimeo_background_params(): void
    {
        $src = EditorHeroBackgroundNormalizer::hardenEmbedSrc(
            'https://player.vimeo.com/video/123456?autoplay=1',
        );

        $this->assertStringContainsString('background=1', $src);
        $this->assertStringContainsString('controls=0', $src);
        $this->assertStringContainsString('muted=1', $src);
        $this->assertStringContainsString('title=0', $src);
    }

    #[Test]
    public function it_leaves_unrelated_markup_unchanged(): void
    {
        $html = '<section class="voodbuilder-editor-section"><p>Hello</p></section>';

        $this->assertSame($html, EditorHeroBackgroundNormalizer::normalize($html));
    }

    #[Test]
    public function it_wraps_orphan_iframe_under_hero_media_and_fixes_wrong_classes(): void
    {
        $html = <<<'HTML'
<div class="voodbuilder-hero-media absolute inset-0">
  <iframe class="voodbuilder-hero-media__video w-full aspect-video vb-video-host" src="https://www.youtube.com/embed/abc123?autoplay=1&controls=0"></iframe>
</div>
HTML;

        $normalized = EditorHeroBackgroundNormalizer::normalize($html);

        $this->assertStringContainsString('voodbuilder-hero-media__embed', $normalized);
        $this->assertStringContainsString('data-vb-embed-bg', $normalized);
        $this->assertStringContainsString('voodbuilder-hero-media__iframe', $normalized);
        $this->assertStringContainsString('mute=1', $normalized);
        $this->assertStringNotContainsString('voodbuilder-hero-media__video', $normalized);
        $this->assertStringNotContainsString('aspect-video', $normalized);
        $this->assertStringNotContainsString('vb-video-host', $normalized);
    }
}
