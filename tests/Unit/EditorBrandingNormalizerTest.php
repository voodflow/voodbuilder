<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorBrandingNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorBrandingNormalizerTest extends TestCase
{
    public function test_replaces_tailblocks_brand_name_and_logo(): void
    {
        $html = '<footer><a><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg><span>Tailblocks</span></a></footer>';

        $normalized = EditorBrandingNormalizer::normalizeHtml($html);

        $this->assertStringContainsString('VoodBuilder', $normalized);
        $this->assertStringNotContainsString('Tailblocks', $normalized);
        $this->assertStringContainsString('voodbuilder-brand-mark', $normalized);
    }

    public function test_does_not_strip_unrelated_svgs_when_replacing_tailblocks_logo(): void
    {
        $html = '<header><button>Submit<svg fill="none" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"></path></svg></button></header>'
            .'<section><h1>Start your journey</h1></section>'
            .'<footer><a><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg><span>Tailblocks</span></a></footer>';

        $normalized = EditorBrandingNormalizer::normalizeHtml($html);

        $this->assertStringContainsString('Start your journey', $normalized);
        $this->assertStringContainsString('M5 12h14M12 5l7 7-7 7', $normalized);
        $this->assertStringContainsString('voodbuilder-brand-mark', $normalized);
        $this->assertStringNotContainsString('Tailblocks', $normalized);
    }

    public function test_preserves_customized_brand_mark_svg_on_render(): void
    {
        $html = '<footer><a><span class="voodbuilder-brand-mark bg-red-500">'
            .'<svg class="voodbuilder-brand-mark__glyph" viewBox="0 0 24 24" style="color: #ff00aa; stroke-width: 3;">'
            .'<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>'
            .'</svg></span><span>VoodBuilder</span></a></footer>';

        $normalized = EditorBrandingNormalizer::normalizeHtml($html);

        $this->assertStringContainsString('style="color: #ff00aa; stroke-width: 3;"', $normalized);
        $this->assertStringContainsString('bg-red-500', $normalized);
        $this->assertStringContainsString('class="voodbuilder-brand-mark bg-red-500"', $normalized);
    }

    public function test_replaces_generic_footer_taglines_with_official_slogan(): void
    {
        $html = '<footer><p>Short description for your brand.</p><p>Air plant banjo lyft occupy retro adaptogen indego</p></footer>';

        $normalized = EditorBrandingNormalizer::normalizeHtml($html);

        $this->assertStringContainsString('A Visual CMS for Laravel & Filament', $normalized);
        $this->assertStringNotContainsString('Short description for your brand', $normalized);
        $this->assertStringNotContainsString('Air plant banjo', $normalized);
    }
}
