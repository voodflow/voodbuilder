<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorCanvas;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
use Voodflow\Voodbuilder\Tests\TestCase;

class DocLayoutChromeTest extends TestCase
{
    public function test_doc_sidebar_stays_in_flow_and_does_not_cover_footer(): void
    {
        $contents = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/views/layouts/doc.blade.php',
        );

        $this->assertStringNotContainsString('fixed top-0 bottom-0', $contents);
        $this->assertStringContainsString('sticky top-[var(--spacing-vp-nav-total)]', $contents);
        $this->assertStringContainsString('vp:flex vp:items-stretch', $contents);
    }

    public function test_landing_css_keeps_full_chrome_bars_with_boxed_nav_footer_containers(): void
    {
        $contents = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/css/landing.css',
        );

        $this->assertStringContainsString(
            "[data-voodbuilder-chrome-width='full'][data-voodbuilder-page-width='full'] [data-voodbuilder-chrome-shell]",
            $contents,
        );
        $this->assertStringContainsString('--voodbuilder-chrome-layout-max, 80rem', $contents);
        $this->assertStringContainsString('padding-inline: 1.25rem', $contents);
    }

    public function test_landing_css_first_child_padding_flush_yields_to_author_utilities(): void
    {
        $contents = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/css/landing.css',
        );

        // Contract: soft first-block chrome flush must not beat Tailwind py-*/pt-*.
        // Unlayered `.VPRichPage--landing > :is(...):first-child { @apply pt-0 }`
        // had specificity ~(0,3,0) and zeroed author py-24 on the published front.
        $this->assertStringNotContainsString(
            '.VPRichPage--landing > :is(section, .voodbuilder-editor-hero, .voodbuilder-editor-cta, .voodbuilder-editor-section, .voodbuilder-editor-footer):first-child {',
            $contents,
        );
        $this->assertStringContainsString(
            ':where(',
            $contents,
        );
        $this->assertMatchesRegularExpression(
            '/@layer\s+components\s*\{[^}]*:where\(\s*\n?\s*\.VPRichPage--landing\s*>\s*:is\(section/s',
            $contents,
        );
        $this->assertStringContainsString('Defaults MUST NOT beat Tailwind utilities', $contents);
    }

    public function test_landing_css_section_traits_and_footer_hero_escape_yield_to_author_utilities(): void
    {
        $contents = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/css/landing.css',
        );

        // Soft section width/padding traits must not be unlayered high-specificity @apply.
        $this->assertStringNotContainsString(
            '.VPRichPage--landing > .vp-landing-section--contained {',
            $contents,
        );
        $this->assertStringNotContainsString(
            '.VPRichPage--landing > .vp-landing-section--padding-default {',
            $contents,
        );
        $this->assertStringNotContainsString(
            '@apply max-w-none px-0 pt-0',
            $contents,
        );
        $this->assertStringContainsString(
            ':where(.VPRichPage--landing > .vp-landing-section--contained)',
            $contents,
        );
        $this->assertStringContainsString(
            ':where(.VPRichPage--landing > .vp-landing-section--padding-default)',
            $contents,
        );
        $this->assertStringContainsString(
            '.VPRichPage--landing .voodbuilder-editor-footer > :is(.container, .voodbuilder-editor-container):has(> .voodbuilder-editor-hero, > section.voodbuilder-editor-hero)',
            $contents,
        );
        $this->assertMatchesRegularExpression(
            '/@layer\s+components\s*\{[\s\S]*:where\(\s*\n?\s*\.VPRichPage--landing\s+\.voodbuilder-editor-footer\s*>/s',
            $contents,
        );

        // Intentional full-bleed chrome stays unlayered.
        $this->assertStringContainsString(
            '.VPRichPage--landing > .vp-landing-section--bleed {',
            $contents,
        );
    }

    public function test_chrome_block_utilities_container_default_yields_to_author_utilities(): void
    {
        $contents = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/css/editor/chrome-block-utilities.css',
        );

        // Unlayered `.voodbuilder-editor-container { max-width }` beat author max-w-*.
        $this->assertDoesNotMatchRegularExpression(
            '/(?<!:where\()\s*\.voodbuilder-editor-container\s*\{\s*\n\s*width:\s*100%;/s',
            $contents,
        );
        $this->assertMatchesRegularExpression(
            '/@layer\s+components\s*\{[^}]*:where\(\.voodbuilder-editor-container\)/s',
            $contents,
        );
        // Toolbar attrs remain unlayered so they still beat the soft default.
        $this->assertStringContainsString(
            ".voodbuilder-editor-container[data-voodbuilder-content-width='full']",
            $contents,
        );
    }

    public function test_chrome_app_main_is_content_driven_not_sticky_footer_by_default(): void
    {
        $chromeApp = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/views/layouts/chrome-app.blade.php',
        );
        $app = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/views/layouts/app.blade.php',
        );
        $landing = (string) file_get_contents(
            VoodbuilderPaths::packagePath().'/resources/css/landing.css',
        );

        // Sticky footer (main flex-1 under body min-h-screen) created a huge gap
        // between short page content and the chrome footer on the published front.
        $this->assertStringNotContainsString('flex flex-1 flex-col', $chromeApp);
        $this->assertStringNotContainsString('<main class="flex-1">', $app);
        $this->assertStringContainsString('body.voodbuilder-sticky-footer > main', $landing);
        $this->assertStringNotContainsString(
            'min-h-[calc(100vh-4rem)] bg-vp-bg',
            $landing,
        );
    }

    public function test_editor_canvas_keeps_full_page_content_edge_to_edge(): void
    {
        $frameStyle = EditorCanvas::frameStyle('site');

        $this->assertStringContainsString(
            "body[data-voodbuilder-canvas-content-width='full'] [data-voodbuilder-page-content]",
            $frameStyle,
        );
        $this->assertStringContainsString(
            '[data-voodbuilder-chrome-drop-zone]:not([data-voodbuilder-page-content])',
            $frameStyle,
        );
    }
}
