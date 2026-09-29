<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\PageSurfaceCssPublish;
use Voodflow\Voodbuilder\Tests\TestCase;

class PageSurfaceCssPublishTest extends TestCase
{
    public function test_remap_for_public_rewrites_orphan_wallpaper_id_to_fixed_layer(): void
    {
        $css = '#iabc123 { background-image: url(/x.jpg); background-attachment: fixed; } .keep { color: red; }';
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

    public function test_remap_for_public_does_not_promote_image_only_block_orphans(): void
    {
        // Element Style photos are often image-only on `#id` (layout via TW classes).
        $css = '#icta { background-image: url(/cta.jpg); background-color: transparent; }';
        $html = '<section class="hero">Hello</section>';

        $remapped = PageSurfaceCssPublish::remapForPublic($css, $html);

        $this->assertStringContainsString('#icta', $remapped);
        $this->assertStringContainsString('url(/cta.jpg)', $remapped);
        $this->assertStringNotContainsString(PageSurfaceCssPublish::bodyTarget(), $remapped);
        $this->assertStringNotContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $remapped);
        $this->assertStringNotContainsString('voodbuilder-site-shell', $remapped);
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
        // Section decoration must not force chrome shells transparent (page wallpaper only).
        $this->assertStringNotContainsString('voodbuilder-site-shell', $remapped);
    }

    public function test_remap_for_public_makes_block_dark_companion_beat_light_important(): void
    {
        $css = "#card { background-image: url('/light.jpg') !important }\n"
            . "html.dark #card {background-image:linear-gradient(rgba(0,0,0,.75),rgba(0,0,0,.75)), url('/dark.jpg');background-color:transparent}";
        $html = '<div id="card" style="background-image:url(/light.jpg)">Card</div>';

        $remapped = PageSurfaceCssPublish::remapForPublic($css, $html);

        $this->assertStringContainsString(
            "html.dark #card {background-image:linear-gradient(rgba(0,0,0,.75),rgba(0,0,0,.75)), url('/dark.jpg') !important;background-color:transparent !important}",
            $remapped,
        );
        $this->assertStringNotContainsString(PageSurfaceCssPublish::darkFixedLayerSelector(), $remapped);
    }

    public function test_remap_for_public_keeps_author_layout_props_on_fixed_layer(): void
    {
        $css = '#iabc123 { background-image: url(/x.jpg); background-size: contain; background-position: top; background-attachment: fixed; }';

        $remapped = PageSurfaceCssPublish::remapForPublic($css, '');

        $this->assertStringContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $remapped);
        $this->assertStringContainsString('background-size:contain', $remapped);
        $this->assertStringContainsString('background-position:top', $remapped);
        $this->assertStringNotContainsString('background-attachment', $remapped);
        $this->assertDoesNotMatchRegularExpression('/background-size:\s*cover/', $remapped);
    }

    public function test_remap_for_public_does_not_promote_section_decoration_orphans(): void
    {
        // Section Style photos get size/position/repeat without attachment:fixed.
        $css = '#icta { background-image: url(/cta.jpg); background-size: cover; background-position: center; background-repeat: no-repeat; }';
        $html = '<section class="hero">Hello</section>';

        $remapped = PageSurfaceCssPublish::remapForPublic($css, $html);

        $this->assertStringContainsString('#icta', $remapped);
        $this->assertStringContainsString('url(/cta.jpg)', $remapped);
        $this->assertStringNotContainsString(PageSurfaceCssPublish::bodyTarget(), $remapped);
        $this->assertStringNotContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $remapped);
        $this->assertStringNotContainsString('voodbuilder-site-shell', $remapped);
    }

    public function test_public_wallpaper_overlay_emits_only_orphan_wallpaper_rules(): void
    {
        $css = <<<'CSS'
#iwrap { background-image: url(/w.jpg); background-position: top; background-attachment: fixed; }
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

    public function test_remap_for_public_rewrites_dark_orphan_wallpaper_to_dark_fixed_layer(): void
    {
        $css = <<<'CSS'
#iwrap { background-image: url(/light.jpg); background-attachment: fixed; }
html.dark #iwrap { background-image: url(/dark.jpg); background-attachment: fixed; }
CSS;

        $remapped = PageSurfaceCssPublish::remapForPublic($css, '');

        $this->assertStringContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $remapped);
        $this->assertStringContainsString(PageSurfaceCssPublish::darkFixedLayerSelector(), $remapped);
        $this->assertStringContainsString('url(/light.jpg)', $remapped);
        $this->assertStringContainsString('url(/dark.jpg)', $remapped);
        $this->assertStringNotContainsString('#iwrap', $remapped);
    }

    public function test_remap_for_public_drops_grapes_comma_html_dark_wallpaper(): void
    {
        $css = <<<'CSS'
#iwrap { background-image: url(/light.jpg); background-size: cover; background-attachment: fixed; }
#iwrap, html.dark { background-image: url(/dark.jpg); background-size: cover; }
html.dark #iwrap { background-image: url(/dark.jpg); background-size: cover; background-attachment: fixed; }
CSS;

        $remapped = PageSurfaceCssPublish::remapForPublic($css, '');

        $this->assertStringNotContainsString('#iwrap, html.dark', $remapped);
        $this->assertStringContainsString('url(/light.jpg)', $remapped);
        $this->assertStringContainsString(PageSurfaceCssPublish::darkFixedLayerSelector(), $remapped);
        $this->assertStringContainsString('url(/dark.jpg)', $remapped);
    }

    public function test_public_wallpaper_overlay_emits_dark_orphan_wallpaper(): void
    {
        $css = 'html.dark #iwrap { background-image: url(/dark.jpg); background-size: cover; background-attachment: fixed; }';

        $overlay = PageSurfaceCssPublish::publicWallpaperOverlay($css, '');

        $this->assertStringContainsString(PageSurfaceCssPublish::darkFixedLayerSelector(), $overlay);
        $this->assertStringContainsString('url(/dark.jpg)', $overlay);
    }

    public function test_public_wallpaper_overlay_keeps_light_and_dark_layers_separate(): void
    {
        $css = <<<'CSS'
#iwrap { background-image: url(/light.jpg); background-size: cover; background-attachment: fixed; }
html.dark #iwrap { background-image: url(/dark.jpg); background-size: cover; background-attachment: fixed; }
CSS;

        $overlay = PageSurfaceCssPublish::publicWallpaperOverlay($css, '');

        $this->assertStringContainsString(PageSurfaceCssPublish::fixedLayerSelector(), $overlay);
        $this->assertStringContainsString(PageSurfaceCssPublish::darkFixedLayerSelector(), $overlay);
        $this->assertStringContainsString('url(/light.jpg)', $overlay);
        $this->assertStringContainsString('url(/dark.jpg)', $overlay);

        // Light layer must not be wrapped in html.dark — otherwise light mode keeps the dark photo.
        $this->assertMatchesRegularExpression(
            '/(?<!html\.dark\s)' . preg_quote(PageSurfaceCssPublish::fixedLayerSelector(), '/')
            . '\s*\{[^}]*url\(\/light\.jpg\)/i',
            $overlay,
        );
        $this->assertMatchesRegularExpression(
            '/' . preg_quote(PageSurfaceCssPublish::darkFixedLayerSelector(), '/') . '\s*\{[^}]*url\(\/dark\.jpg\)/i',
            $overlay,
        );

        // Regression: light ::before must NEVER carry the dark URL (PCRE used to
        // match `body.CLASS` inside `html.dark body.CLASS` and leak dark → light).
        $this->assertDoesNotMatchRegularExpression(
            '/(?<!html\.dark\s)' . preg_quote(PageSurfaceCssPublish::fixedLayerSelector(), '/')
            . '\s*\{[^}]*url\(\/dark\.jpg\)/i',
            $overlay,
        );
    }

    public function test_remap_for_public_does_not_leak_dark_wallpaper_onto_light_before(): void
    {
        // Mirrors a real Save sheet: dual wrapper orphans with attachment:fixed,
        // PLUS a layout-only dark companion that used to block dark remap (first
        // match) and let light remap corrupt `html.dark #id` into bodyTarget.
        $css = <<<'CSS'
#i9a5 {background-image:url('/storage/7/light.webp');background-size:cover;background-position:top;background-repeat:no-repeat;background-attachment:fixed}
html.dark #i9a5 {background-image:url('/storage/6/dark.webp');background-size:cover;background-position:top;background-repeat:no-repeat;background-attachment:fixed}
#ispi {background-image:url('/storage/7/light.webp');background-size:cover;background-position:top;background-repeat:no-repeat;background-attachment:fixed}
html.dark #ispi {background-size:cover;background-position:top;background-repeat:no-repeat}
html.dark #ispi {background-image:url('/storage/6/dark.webp');background-size:cover;background-position:top;background-repeat:no-repeat;background-attachment:fixed}
CSS;

        $remapped = PageSurfaceCssPublish::remapForPublic($css, '');

        $this->assertStringNotContainsString('html.dark html, body', $remapped);

        preg_match_all(
            '/(?<!html\.dark\s)' . preg_quote(PageSurfaceCssPublish::fixedLayerSelector(), '/') . '\s*\{([^}]*)\}/i',
            $remapped,
            $lightLayers,
        );
        preg_match_all(
            '/' . preg_quote(PageSurfaceCssPublish::darkFixedLayerSelector(), '/') . '\s*\{([^}]*)\}/i',
            $remapped,
            $darkLayers,
        );

        $this->assertNotEmpty($lightLayers[1]);
        $this->assertNotEmpty($darkLayers[1]);

        foreach ($lightLayers[1] as $body) {
            $this->assertStringContainsString('/storage/7/light.webp', $body);
            $this->assertStringNotContainsString('/storage/6/dark.webp', $body);
        }

        foreach ($darkLayers[1] as $body) {
            $this->assertStringContainsString('/storage/6/dark.webp', $body);
            $this->assertStringNotContainsString('/storage/7/light.webp', $body);
        }
    }
}
