<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorBackgroundPublishNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorBackgroundPublishNormalizerTest extends TestCase
{
    public function test_strips_inline_background_url_when_css_already_paints_it(): void
    {
        $html = '<section id="hero" style="background-image: url(\'/storage/3/photo-lg.webp\'); background-size: cover;" class="hero">Hi</section>';
        $css = '#hero{background-image:url(\'/storage/3/photo-lg.webp\');background-size:cover}';

        $out = EditorBackgroundPublishNormalizer::preferCssBackgrounds($html, $css);

        $this->assertStringNotContainsString('url(', $out);
        $this->assertStringContainsString('background-size: cover', $out);
        $this->assertStringContainsString('class="hero"', $out);
    }

    public function test_keeps_inline_background_when_css_has_no_matching_url(): void
    {
        $html = '<section style="background-image: url(\'/storage/only-inline.jpg\');">Hi</section>';
        $css = '.other{color:red}';

        $out = EditorBackgroundPublishNormalizer::preferCssBackgrounds($html, $css);

        $this->assertStringContainsString("url('/storage/only-inline.jpg')", $out);
    }

    public function test_matches_absolute_and_root_relative_urls(): void
    {
        $html = '<div style="background-image:url(https://example.test/storage/x-lg.webp)"></div>';
        $css = 'body{background-image:url(/storage/x-lg.webp)}';

        $out = EditorBackgroundPublishNormalizer::preferCssBackgrounds($html, $css);

        $this->assertStringNotContainsString('url(', $out);
    }
}
