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
        $this->assertStringNotContainsString('background-size', $out);
        $this->assertStringNotContainsString('style=', $out);
        $this->assertStringContainsString('class="hero"', $out);
    }

    public function test_strips_full_inline_paint_when_id_rule_owns_background(): void
    {
        $html = '<section id="cta" style="background-color:transparent;background-size:cover;background-position:center;background-repeat:no-repeat;background-image:url(\'/storage/4/photo-lg.webp\');">Hi</section>';
        $css = '#cta{background-image:url(\'/storage/4/photo-lg.webp\');background-size:cover}';

        $out = EditorBackgroundPublishNormalizer::preferCssBackgrounds($html, $css);

        $this->assertStringNotContainsString('style=', $out);
        $this->assertStringContainsString('id="cta"', $out);
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
