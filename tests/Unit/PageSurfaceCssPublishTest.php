<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\PageSurfaceCssPublish;
use Voodflow\Voodbuilder\Tests\TestCase;

class PageSurfaceCssPublishTest extends TestCase
{
    public function test_remap_for_public_rewrites_orphan_wallpaper_id_to_fixed_layer(): void
    {
        $css = '#iabc123 { background-image: url(/x.jpg); } .keep { color: red; }';
        $html = '<section class="hero">Hello</section>';

        $remapped = PageSurfaceCssPublish::remapForPublic($css, $html);

        $this->assertStringContainsString(PageSurfaceCssPublish::bodyTarget(), $remapped);
        $this->assertStringContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $remapped);
        $this->assertStringContainsString('position:fixed', $remapped);
        $this->assertStringContainsString('background-image:url(/x.jpg)', $remapped);
        $this->assertStringContainsString('background-size:cover', $remapped);
        $this->assertStringContainsString('background-position:center', $remapped);
        $this->assertStringContainsString('background-repeat:no-repeat', $remapped);
        $this->assertStringNotContainsString('background-attachment', $remapped);
        $this->assertStringNotContainsString('#iabc123', $remapped);
        $this->assertStringContainsString('.keep { color: red; }', $remapped);
        $this->assertStringContainsString('background-color: transparent !important', $remapped);
        $this->assertStringContainsString('.voodbuilder-site-shell', $remapped);
        $this->assertStringContainsString('background-image: none', $remapped);
    }

    public function test_remap_for_public_keeps_ids_present_in_html(): void
    {
        $css = '#hero { background-image: url(/x.jpg); background-size: contain; }';
        $html = '<section id="hero">Hello</section>';

        $remapped = PageSurfaceCssPublish::remapForPublic($css, $html);

        $this->assertStringContainsString('#hero', $remapped);
        $this->assertStringContainsString('background-size: contain', $remapped);
        $this->assertStringNotContainsString(PageSurfaceCssPublish::bodyTarget(), $remapped);
        $this->assertStringNotContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $remapped);
    }

    public function test_remap_for_public_keeps_author_layout_props_on_fixed_layer(): void
    {
        $css = '#iabc123 { background-image: url(/x.jpg); background-size: contain; background-position: top; }';

        $remapped = PageSurfaceCssPublish::remapForPublic($css, '');

        $this->assertStringContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $remapped);
        $this->assertStringContainsString('background-size:contain', $remapped);
        $this->assertStringContainsString('background-position:top', $remapped);
        $this->assertStringNotContainsString('background-attachment', $remapped);
        $this->assertDoesNotMatchRegularExpression('/background-size:\s*cover/', $remapped);
    }

    public function test_public_wallpaper_overlay_emits_only_orphan_wallpaper_rules(): void
    {
        $css = <<<'CSS'
#iwrap { background-image: url(/w.jpg); background-position: top; }
#hero { background-image: url(/h.jpg); }
.keep { color: red; }
CSS;
        $html = '<section id="hero">Hi</section>';

        $overlay = PageSurfaceCssPublish::publicWallpaperOverlay($css, $html);

        $this->assertStringContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $overlay);
        $this->assertStringContainsString('url(/w.jpg)', $overlay);
        $this->assertStringContainsString('background-position:top', $overlay);
        $this->assertStringNotContainsString('url(/h.jpg)', $overlay);
        $this->assertStringNotContainsString('.keep', $overlay);
        $this->assertStringContainsString('background-color: transparent !important', $overlay);
        $this->assertStringContainsString('.voodbuilder-site-shell', $overlay);
    }

    public function test_public_wallpaper_overlay_transparent_shells_for_legacy_body_rules(): void
    {
        $css = 'html, body { background-image: url(/legacy.jpg); background-size: cover; background-attachment: fixed; }';

        $overlay = PageSurfaceCssPublish::publicWallpaperOverlay($css, '');

        $this->assertStringContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $overlay);
        $this->assertStringContainsString('url(/legacy.jpg)', $overlay);
        $this->assertStringNotContainsString('background-attachment', $overlay);
        $this->assertStringContainsString('background-color: transparent !important', $overlay);
        $this->assertStringContainsString('.voodbuilder-events-shell', $overlay);
    }
}
