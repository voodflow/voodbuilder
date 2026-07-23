<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentPageHtml;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentTailwindCompiler;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsPastedComponentNormalizerTest extends TestCase
{
    public function test_extracts_style_tags_and_strips_scripts(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <style>.hero { color: red; }</style>
            <section class="hero"><h1>Hello</h1></section>
            <script>alert(1)</script>
        HTML);

        $this->assertStringContainsString('voodbuilder-pasted-component relative', $result['html']);
        $this->assertStringNotContainsString('<style', $result['html']);
        $this->assertStringNotContainsString('<script', $result['html']);
        $this->assertStringContainsString('.hero { color: red; }', $result['css']);
        $this->assertStringContainsString('.voodbuilder-pasted-component { position: relative; }', $result['css']);
    }

    public function test_extracts_body_from_full_document(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <!DOCTYPE html>
            <html><head><title>x</title></head>
            <body><main>Content</main></body></html>
        HTML);

        $this->assertSame('<main class="voodbuilder-pasted-component relative">Content</main>', $result['html']);
    }

    public function test_wraps_multiple_root_sections(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <nav>Menu</nav>
            <section>Hero</section>
        HTML);

        $this->assertStringStartsWith('<div class="voodbuilder-pasted-component relative">', $result['html']);
        $this->assertStringContainsString('<nav>Menu</nav>', $result['html']);
        $this->assertStringContainsString('<section class="voodbuilder-gjs-section bg-vp-bg">Hero</section>', $result['html']);
    }

    public function test_uniquifies_svg_gradient_ids(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <svg><defs><linearGradient id="paint0"></linearGradient></defs>
            <rect fill="url(#paint0)"></rect></svg>
        HTML);

        $this->assertDoesNotMatchRegularExpression('/\bid="paint0"/', $result['html']);
        $this->assertMatchesRegularExpression('/\bid="paint0-vb-[a-f0-9]+"/', $result['html']);
        $this->assertMatchesRegularExpression('/url\(#paint0-vb-[a-f0-9]+\)/', $result['html']);
    }

    public function test_strips_video_and_embedded_players_from_saved_html(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <section class="hero">
                <h1>Title</h1>
                <video autoplay muted loop playsinline src="https://example.com/clip.mp4"></video>
                <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1"></iframe>
                <div data-gjs-type="video" class="w-full aspect-video"></div>
            </section>
        HTML);

        $this->assertStringContainsString('<h1>Title</h1>', $result['html']);
        $this->assertStringNotContainsString('<video', $result['html']);
        $this->assertStringNotContainsString('<iframe', $result['html']);
        $this->assertStringNotContainsString('data-gjs-type="video"', $result['html']);
        $this->assertStringContainsString('voodbuilder-component-library-media-slot', $result['html']);
    }

    public function test_normalizes_tailwind_plus_markup_and_generates_component_css(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <div class="bg-white">
              <header class="absolute inset-x-0 top-0 z-50">
                <el-dialog>
                  <dialog id="mobile-menu" class="lg:hidden">
                    <el-dialog-panel class="bg-white p-6"></el-dialog-panel>
                  </dialog>
                </el-dialog>
              </header>
              <div class="relative isolate px-6 pt-14">
                <div class="bg-linear-to-tr from-[#ff80b5] to-[#9089fc] w-144.5 aspect-1155/678"></div>
                <a href="#" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Get started</a>
                <p class="text-sm/6 text-indigo-600 text-balance">Read more</p>
              </div>
            </div>
        HTML);

        $this->assertStringContainsString('voodbuilder-pasted-component', $result['html']);
        $this->assertStringContainsString('bg-gradient-to-tr', $result['html']);
        $this->assertStringContainsString('text-sm leading-6', $result['html']);
        $this->assertStringContainsString('shadow-sm', $result['html']);
        $this->assertStringNotContainsString('<el-dialog', $result['html']);
        $this->assertStringContainsString('.voodbuilder-pasted-component { position: relative; }', (string) $result['css']);
        $this->assertStringContainsString('.voodbuilder-pasted-component .bg-vp-brand-3', (string) $result['css']);
    }

    public function test_adds_dark_scope_for_mamba_style_dark_variant_classes(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <footer class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                <h2 class="text-violet-600 dark:text-violet-400">Accent</h2>
            </footer>
        HTML);

        $this->assertStringContainsString('voodbuilder-pasted-component', $result['html']);
        $this->assertMatchesRegularExpression('/\bclass="[^"]*\bdark\b[^"]*voodbuilder-pasted-component/', $result['html']);
        $this->assertStringContainsString(':is(.dark', (string) $result['css']);
        $this->assertStringNotContainsString('prefers-color-scheme: dark', (string) $result['css']);
    }

    public function test_detects_legacy_brand_utilities_in_component_css(): void
    {
        $this->assertTrue(GrapesJsPastedComponentNormalizer::cssReferencesLegacyBrandUtilities(
            '.voodbuilder-pasted-component .bg-indigo-600 { background: #4f46e5; }',
        ));
        $this->assertFalse(GrapesJsPastedComponentNormalizer::cssReferencesLegacyBrandUtilities(
            '.voodbuilder-pasted-component .bg-vp-brand-1 { background: var(--color-vp-brand-1); }',
        ));
    }

    public function test_resolved_css_recompiles_when_legacy_utilities_are_present(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><button class="bg-indigo-600 text-white">Go</button></div>';
        $legacyCss = '.voodbuilder-pasted-component .bg-indigo-600 { background-color: #4f46e5; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($html, $legacyCss);

        $this->assertStringContainsString('.bg-vp-brand-3', $resolved);
        $this->assertStringNotContainsString('.bg-indigo-600', $resolved);
        $this->assertStringContainsString('var(--color-vp-brand', $resolved);
        $this->assertStringContainsString('--color-vp-brand-1: inherit', $resolved);
    }

    public function test_resolved_css_strips_pinned_theme_token_overrides_from_stored_css(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><button class="bg-vp-brand-3 text-white">Go</button></div>';
        $storedCss = '.voodbuilder-pasted-component { --color-vp-brand-3: #6366f1; } .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($html, $storedCss);

        $this->assertStringNotContainsString('#6366f1', $resolved);
        $this->assertStringContainsString('--color-vp-brand-3: inherit', $resolved);
    }

    public function test_resolved_css_recompiles_when_stored_css_pins_tailwind_indigo_variables(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><a class="bg-vp-brand-3 text-white hover:bg-vp-brand-2">Contact</a></div>';
        $storedCss = '.voodbuilder-pasted-component { --color-indigo-600: oklch(51.1% 0.262 276.966); }'
            .' .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($html, $storedCss);

        $this->assertStringNotContainsString('--color-indigo-600', $resolved);
        $this->assertStringContainsString('var(--color-vp-brand-3)', $resolved);
    }

    public function test_html_legacy_brand_utilities_trigger_recompile_path(): void
    {
        $this->assertTrue(GrapesJsPastedComponentNormalizer::htmlReferencesLegacyBrandUtilities(
            '<button class="rounded-md bg-indigo-600 hover:bg-indigo-500">Contact</button>',
        ));
        $this->assertTrue(GrapesJsPastedComponentNormalizer::htmlReferencesLegacyBrandUtilities(
            '<a class="focus-visible:outline-indigo-600">Contact</a>',
        ));
    }

    public function test_normalizes_preline_semantic_utilities_and_generates_theme_colors(): void
    {
        $result = GrapesJsPastedComponentNormalizer::normalize(<<<'HTML'
            <div class="voodbuilder-pasted-component">
              <h1 class="text-3xl font-bold text-foreground">Start with <span class="text-primary">Preline</span></h1>
              <a class="bg-primary text-primary-foreground hover:bg-primary-hover border border-primary-line" href="#">Get started</a>
              <a class="bg-layer text-layer-foreground hover:bg-layer-hover border border-layer-line" href="#">Contact</a>
            </div>
        HTML);

        $css = (string) $result['css'];

        $this->assertStringContainsString('text-primary', $css);
        $this->assertStringContainsString('bg-primary', $css);
        $this->assertStringContainsString('var(--color-primary)', $css);
        $this->assertStringContainsString('--color-primary: var(--color-vp-brand-2)', $css);
    }

    public function test_resolved_css_recompiles_when_preline_semantic_utilities_are_missing(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><span class="text-primary bg-primary">Preline</span></div>';
        $storedCss = '.voodbuilder-pasted-component { position: relative; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($html, $storedCss);

        $this->assertStringContainsString('.text-primary', $resolved);
        $this->assertStringContainsString('.bg-primary', $resolved);
    }

    public function test_resolved_css_recompiles_when_new_classes_are_added_after_import(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="mx-auto w-2/5 bg-vp-brand-3">Box</div></div>';
        $storedCss = '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($html, $storedCss);

        $this->assertStringContainsString('.mx-auto', $resolved);
        $this->assertStringContainsString('.w-2\\/5', $resolved);
        $this->assertStringContainsString('.bg-vp-brand-3', $resolved);
    }

    public function test_catalog_css_uses_stored_css_without_tailwind_recompilation(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="mx-auto w-2/5 bg-vp-brand-3">Box</div></div>';
        $storedCss = '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';

        $resolved = GrapesJsPastedComponentNormalizer::resolveCatalogCss(
            $html,
            $storedCss,
            GrapesJsPastedComponentNormalizer::htmlChecksum($html),
        );

        $this->assertStringContainsString('.bg-vp-brand-3', $resolved['css']);
        $this->assertStringNotContainsString('.mx-auto', $resolved['css']);
        $this->assertStringNotContainsString('.w-2\\/5', $resolved['css']);
        $this->assertNull($resolved['cssToPersist']);
    }

    public function test_catalog_css_marks_new_components_for_persistence(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="bg-vp-brand-3">Box</div></div>';

        $resolved = GrapesJsPastedComponentNormalizer::resolveCatalogCss($html, null);

        $this->assertNotSame('', $resolved['css']);
        $this->assertNotNull($resolved['cssToPersist']);
        $this->assertNotNull($resolved['htmlChecksumToPersist']);
        $this->assertStringContainsString('.bg-vp-brand-3', $resolved['cssToPersist']);
    }

    public function test_published_css_never_compiles_tailwind_utilities(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="mx-auto w-2/5 bg-vp-brand-3">Box</div></div>';
        $storedCss = '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';

        $published = GrapesJsPastedComponentNormalizer::publishedCssForStoredHtml($html, $storedCss);

        $this->assertStringContainsString('.bg-vp-brand-3', $published);
        $this->assertStringNotContainsString('.mx-auto', $published);
        $this->assertStringNotContainsString('.w-2\\/5', $published);
    }

    public function test_stored_css_is_corrupted_detects_truncated_var_references(): void
    {
        $corrupted = '.bg-blue-200 { background-color: var( }';

        $this->assertTrue(GrapesJsPastedComponentNormalizer::storedCssIsCorrupted($corrupted));
        $this->assertTrue(GrapesJsPastedComponentNormalizer::storedCssRequiresRecompile(
            '<div class="bg-blue-200">Box</div>',
            $corrupted,
        ));
    }

    public function test_resolve_published_page_css_recompiles_when_stored_css_is_corrupted(): void
    {
        $html = '<section class="bg-blue-200 p-4"><p class="text-white">Hi</p></section>';
        $corrupted = '.bg-blue-200 { background-color: var( }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCss($html, $corrupted);

        $this->assertFalse(GrapesJsPastedComponentNormalizer::storedCssIsCorrupted($resolved));
        $this->assertStringContainsString('var(--color-blue-200,', $resolved);
    }

    public function test_resolve_published_page_css_adds_palette_fallbacks_when_variables_were_stripped(): void
    {
        $html = '<section class="bg-blue-200 p-4">Hi</section>';
        $storedCss = '.bg-blue-200 { background-color: var(--color-blue-200); } .p-4 { padding: 1rem; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCss($html, $storedCss);

        $this->assertStringContainsString('var(--color-blue-200,', $resolved);
        $this->assertStringNotContainsString('background-color: var( }', $resolved);
    }

    public function test_resolve_published_page_css_recompiles_when_html_adds_new_tailwind_utility(): void
    {
        $html = '<section class="bg-blue-200 p-4">Hi</section>';
        $storedCss = '.p-4 { padding: 1rem; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCss($html, $storedCss);

        $this->assertStringContainsString('bg-blue-200', $resolved);
        $this->assertStringContainsString('padding', $resolved);
    }

    public function test_resolve_published_page_css_ignores_component_instance_utilities(): void
    {
        $html = '<section class="p-4">Page</section>'
            .'<div data-voodbuilder-component="cmp-1"><div class="voodbuilder-pasted-component"><div class="bg-blue-200">Card</div></div></div>';
        $storedCss = '.p-4 { padding: 1rem; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCss($html, $storedCss);

        $this->assertStringContainsString('padding', $resolved);
        $this->assertStringNotContainsString('bg-blue-200', $resolved);
    }

    public function test_resolve_published_page_css_for_save_always_recompiles_from_html(): void
    {
        $html = '<section class="bg-red-200 p-4">Hi</section>';
        $storedCss = '.bg-blue-200 { background-color: var(--color-blue-200); } .p-4 { padding: 1rem; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCssForSave($html, $storedCss);

        $this->assertStringContainsString('bg-red-200', $resolved);
        $this->assertStringNotContainsString('bg-blue-200', $resolved);
        $this->assertStringContainsString('var(--color-red-200,', $resolved);
        $this->assertStringNotContainsString('.voodbuilder-pasted-component .bg-red-200', $resolved);
    }

    public function test_resolve_published_page_css_for_save_keeps_grapes_composer_rules(): void
    {
        $html = '<section id="iabc" class="bg-red-200 p-4">Hi</section>';
        $storedCss = '#iabc { margin-top: 2rem; } .bg-blue-200 { background-color: var(--color-blue-200); }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCssForSave($html, $storedCss);

        $this->assertStringContainsString('#iabc', $resolved);
        $this->assertStringContainsString('margin-top', $resolved);
        $this->assertStringContainsString('bg-red-200', $resolved);
        $this->assertStringNotContainsString('bg-blue-200', $resolved);
    }

    public function test_resolve_published_page_css_for_save_strips_wrapper_theme_token_rules(): void
    {
        $html = '<section>Updated</section>';
        $storedCss = '.updated { color: red; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCssForSave($html, $storedCss);

        $this->assertSame('.updated {color: red;}', $resolved);
    }

    public function test_resolve_published_page_css_keeps_typography_tokens_for_frontend(): void
    {
        $html = '<h1 class="text-3xl sm:text-4xl font-semibold">Hero</h1>';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCssForSave($html, '');

        $this->assertStringContainsString('text-3xl', $resolved);
        $this->assertTrue(
            str_contains($resolved, '--text-3xl:')
            || str_contains($resolved, 'var(--text-3xl,'),
            'Published CSS must define or fallback --text-3xl so frontend is not flat.',
        );
        $this->assertTrue(
            str_contains($resolved, '--text-4xl:')
            || str_contains($resolved, 'var(--text-4xl,'),
            'Published CSS must define or fallback --text-4xl for sm:text-4xl.',
        );
    }

    public function test_strip_published_page_css_keeps_structural_root_tokens(): void
    {
        $css = <<<'CSS'
:root {
    --spacing: 0.25rem;
    --text-3xl: 1.875rem;
    --color-white: #fff;
    --color-indigo-500: #6366f1;
    --color-vp-brand-1: #ff0000;
}
.text-white {
    color: var(--color-white);
}
.text-3xl {
    font-size: var(--text-3xl);
}
CSS;

        $stripped = GrapesJsPastedComponentNormalizer::stripPublishedPageCssRuntimeStyles($css);

        $this->assertStringContainsString('--spacing', $stripped);
        $this->assertStringContainsString('--text-3xl', $stripped);
        $this->assertStringContainsString('--color-white', $stripped);
        $this->assertStringNotContainsString('--color-vp-brand-1', $stripped);
        $this->assertStringNotContainsString('--color-indigo-500', $stripped);
        $this->assertStringContainsString('.text-white', $stripped);
        $this->assertStringContainsString('.text-3xl', $stripped);
    }

    public function test_grapes_composer_rules_from_stored_css_extracts_id_selectors_only(): void
    {
        $storedCss = '#iabc { color: red; } .bg-blue-200 { background-color: blue; }';

        $manual = GrapesJsPastedComponentNormalizer::grapesComposerRulesFromStoredCss($storedCss);

        $this->assertStringContainsString('#iabc', $manual);
        $this->assertStringNotContainsString('bg-blue-200', $manual);
    }

    public function test_manual_page_css_from_stored_css_keeps_custom_classes(): void
    {
        $storedCss = '#iabc { margin-top: 2rem; } .updated { color: red; } .bg-blue-200 { background-color: blue; }';

        $manual = GrapesJsPastedComponentNormalizer::manualPageCssFromStoredCss($storedCss);

        $this->assertStringContainsString('#iabc', $manual);
        $this->assertStringContainsString('.updated', $manual);
        $this->assertStringNotContainsString('bg-blue-200', $manual);
    }

    public function test_dedupe_css_rules_collapses_identical_rules(): void
    {
        $css = ".hero { color: red; }\n.hero { color: red; }\n.other { color: blue; }";

        $deduped = GrapesJsPastedComponentNormalizer::dedupeCssRules($css);

        $this->assertSame(".hero { color: red; }\n.other { color: blue; }", $deduped);
    }

    public function test_manual_page_css_from_stored_css_strips_theme_header_chrome(): void
    {
        $storedCss = "html[data-voodbuilder-sub-theme] header[role='banner'] .voodbuilder-header-icon-btn { color: red; } #hero { color: blue; }";

        $manual = GrapesJsPastedComponentNormalizer::manualPageCssFromStoredCss($storedCss);

        $this->assertStringContainsString('#hero', $manual);
        $this->assertStringNotContainsString('voodbuilder-header-icon-btn', $manual);
    }

    public function test_page_css_includes_tailwind_preflight_detects_button_reset(): void
    {
        $preflight = '* { box-sizing: border-box; } button, input, ::file-selector-button { border-radius: 0; } .p-4 { padding: 1rem; }';

        $this->assertTrue(GrapesJsPastedComponentNormalizer::pageCssIncludesTailwindPreflight($preflight));
        $stripped = GrapesJsPastedComponentNormalizer::stripTailwindPreflightFromPageCss($preflight);
        $this->assertStringNotContainsString('border-radius: 0', $stripped);
        $this->assertStringContainsString('.p-4', $stripped);
    }

    public function test_page_css_includes_tailwind_preflight_detects_tailwind_v4_host_reset(): void
    {
        $preflight = 'html, :host { line-height: 1.5; -webkit-text-size-adjust: 100%; tab-size: 4; } .bg-red-200 { background-color: red; }';

        $this->assertTrue(GrapesJsPastedComponentNormalizer::pageCssIncludesTailwindPreflight($preflight));
    }

    public function test_manual_page_css_from_stored_css_strips_preflight_element_selectors(): void
    {
        $storedCss = 'html, :host { line-height: 1.5; -webkit-text-size-adjust: 100%; } hr { height: 0; } #hero { color: red; } .md\\:block {}';

        $manual = GrapesJsPastedComponentNormalizer::manualPageCssFromStoredCss($storedCss);

        $this->assertStringContainsString('#hero', $manual);
        $this->assertStringNotContainsString('html, :host', $manual);
        $this->assertStringNotContainsString('hr {', $manual);
        $this->assertStringNotContainsString('.md\\:block', $manual);
    }

    public function test_html_for_page_tailwind_compile_keeps_site_header_classes(): void
    {
        $html = '<div data-voodbuilder-gjs-site-header><header class="px-6 md:px-8">Nav</header></div><section class="bg-red-200">Body</section>';

        $compiledHtml = GrapesJsComponentPageHtml::htmlForPageTailwindCompile($html);

        $this->assertStringContainsString('data-voodbuilder-gjs-site-header', $compiledHtml);
        $this->assertStringContainsString('px-6', $compiledHtml);
        $this->assertStringContainsString('bg-red-200', $compiledHtml);
    }

    public function test_resolve_published_page_css_strips_preflight_without_recompile(): void
    {
        $html = '<section class="p-4">Page</section>';
        $storedCss = '* { box-sizing: border-box; } button, ::file-selector-button { border-radius: 0; background-color: transparent; } .p-4 { padding: 1rem; }';

        $resolved = GrapesJsPastedComponentNormalizer::resolvePublishedPageCss($html, $storedCss);

        $this->assertStringNotContainsString('border-radius: 0', $resolved);
        $this->assertStringContainsString('padding', $resolved);
    }

    public function test_page_css_missing_theme_variables_detects_spacing_without_root(): void
    {
        $broken = '.p-4 { padding: calc(var(--spacing) * 4); }';
        $fixed = ':root { --spacing: 0.25rem; } .p-4 { padding: calc(var(--spacing, 0.25rem) * 4); }';

        $this->assertTrue(GrapesJsPastedComponentNormalizer::pageCssMissingThemeVariables($broken));
        $this->assertFalse(GrapesJsPastedComponentNormalizer::pageCssMissingThemeVariables($fixed));
    }

    public function test_page_tailwind_compile_omits_broken_container_rules(): void
    {
        if (! GrapesJsComponentTailwindCompiler::isAvailable()) {
            $this->markTestSkipped('Node Tailwind compiler is not available.');
        }

        $html = '<div class="container px-5 mx-auto"><div class="p-4 md:w-1/3">Card</div></div>';
        $css = GrapesJsPastedComponentNormalizer::compilePageTailwindCss($html);

        $this->assertStringNotContainsString('.container {', $css);
        $this->assertStringContainsString('md:w-1/3', str_replace('\\', '', $css));
    }

    public function test_page_tailwind_compile_keeps_default_palette_color_tokens(): void
    {
        if (! GrapesJsComponentTailwindCompiler::isAvailable()) {
            $this->markTestSkipped('Node Tailwind compiler is not available.');
        }

        $html = '<section class="bg-red-800 p-4">Hero</section>';
        $css = GrapesJsPastedComponentNormalizer::compilePageTailwindCss($html);

        $this->assertStringContainsString('bg-red-800', str_replace('\\', '', $css));
        $this->assertStringContainsString('--color-red-800', $css);
        $this->assertMatchesRegularExpression(
            '/\.bg-red-800\s*\{[^}]*background-color:\s*var\(--color-red-800\)/s',
            $css,
        );
    }

    public function test_stored_css_is_current_when_checksum_matches(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="bg-vp-brand-3">Box</div></div>';
        $checksum = GrapesJsPastedComponentNormalizer::htmlChecksum($html);
        $storedCss = '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';

        $this->assertTrue(GrapesJsPastedComponentNormalizer::storedCssIsCurrent($html, $storedCss, $checksum));
    }

    public function test_resolved_css_skips_compile_when_checksum_matches(): void
    {
        $html = '<div class="voodbuilder-pasted-component"><div class="mx-auto w-2/5 bg-vp-brand-3">Box</div></div>';
        $storedCss = '.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }';
        $checksum = GrapesJsPastedComponentNormalizer::htmlChecksum($html);

        $resolved = GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml($html, $storedCss, $checksum);

        $this->assertStringContainsString('.bg-vp-brand-3', $resolved);
        $this->assertStringNotContainsString('.mx-auto', $resolved);
    }
}
