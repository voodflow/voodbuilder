<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsImportCompatibilityAnalyzer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsImportCompatibilityAnalyzerTest extends TestCase
{
    public function test_reports_preline_utilities_as_ready_when_compiled(): void
    {
        $raw = <<<'HTML'
            <div class="bg-linear-to-tr from-surface-1 text-primary bg-primary hover:bg-primary-hover">
                <h1 class="text-foreground">Title</h1>
            </div>
        HTML;

        $normalized = GrapesJsPastedComponentNormalizer::normalize($raw);
        $css = GrapesJsPastedComponentNormalizer::compileTailwindCss((string) $normalized['html']);

        if ($css === '') {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, (string) $normalized['html'], $css);

        $this->assertContains('bg-gradient-to-tr', array_column($report['adaptations'], 'to'));
        $this->assertSame(0, $report['totals']['review']);
        $this->assertContains($report['status'], ['excellent', 'good']);
    }

    public function test_flags_unknown_utilities_for_review(): void
    {
        $raw = '<div class="text-primary totally-made-up-utility">X</div>';
        $normalized = GrapesJsPastedComponentNormalizer::normalize($raw);
        $css = GrapesJsPastedComponentNormalizer::compileTailwindCss((string) $normalized['html']);

        if ($css === '') {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, (string) $normalized['html'], $css);

        $this->assertContains('totally-made-up-utility', $report['review']);
        $this->assertGreaterThan(0, $report['totals']['review']);
    }

    public function test_ignores_voodbuilder_infrastructure_classes_in_review(): void
    {
        $raw = '<section class="voodbuilder-gjs-section"><div class="voodbuilder-gjs-container px-5 py-24"><p class="text-vp-text-2">Hello</p></div></section>';
        $normalized = GrapesJsPastedComponentNormalizer::normalize($raw);
        $css = GrapesJsPastedComponentNormalizer::compileTailwindCss((string) $normalized['html']);

        if ($css === '') {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, (string) $normalized['html'], $css);

        $this->assertNotContains('voodbuilder-gjs-section', $report['review']);
        $this->assertNotContains('voodbuilder-gjs-container', $report['review']);
    }

    public function test_ignores_all_internal_package_class_prefixes_in_review(): void
    {
        $raw = <<<'HTML'
            <section class="vb-animated-stats voodbuilder-gjs-section">
                <div class="voodbuilder-gjs-inner-drop-slot">
                    <span class="vb-animated-counter">10</span>
                </div>
            </section>
        HTML;

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, $raw, '');

        $this->assertNotContains('vb-animated-stats', $report['review']);
        $this->assertNotContains('vb-animated-counter', $report['review']);
        $this->assertNotContains('voodbuilder-gjs-section', $report['review']);
        $this->assertNotContains('voodbuilder-gjs-inner-drop-slot', $report['review']);
        $this->assertTrue(GrapesJsImportCompatibilityAnalyzer::isInternalPackageClass('hover:vb-animated-counter'));
        $this->assertFalse(GrapesJsImportCompatibilityAnalyzer::isInternalPackageClass('totally-made-up-utility'));
    }

    public function test_treats_standard_tailwind_utilities_as_theme_ready_without_jit(): void
    {
        $raw = <<<'HTML'
            <section class="grid grid-cols-2 md:grid-cols-6 gap-4 mt-12 font-bold flex-wrap bg-gradient-to-br animate-spin blur-[106px]">
                Hero
            </section>
        HTML;

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, $raw, '');

        $this->assertNotContains('grid', $report['review']);
        $this->assertNotContains('grid-cols-2', $report['review']);
        $this->assertNotContains('md:grid-cols-6', $report['review']);
        $this->assertNotContains('mt-12', $report['review']);
        $this->assertNotContains('font-bold', $report['review']);
        $this->assertNotContains('flex-wrap', $report['review']);
        $this->assertNotContains('bg-gradient-to-br', $report['review']);
        $this->assertNotContains('animate-spin', $report['review']);
        $this->assertNotContains('blur-[106px]', $report['review']);
        $this->assertSame(0, $report['totals']['review']);
        $this->assertContains($report['status'], ['excellent', 'good']);
        $this->assertTrue(GrapesJsImportCompatibilityAnalyzer::isStandardCanvasUtility('md:text-6xl'));
        $this->assertFalse(GrapesJsImportCompatibilityAnalyzer::isStandardCanvasUtility('totally-made-up-utility'));
    }

    public function test_css_includes_utility_detects_escaped_variants(): void
    {
        $css = '.voodbuilder-pasted-component .hover\\:bg-primary-hover:hover { background-color: red; }';

        $this->assertTrue(GrapesJsImportCompatibilityAnalyzer::cssIncludesUtility($css, 'hover:bg-primary-hover'));
    }

    public function test_treats_vp_and_theme_utilities_as_ready_from_theme_reference(): void
    {
        $raw = '<header class="hidden vp:flex bg-vp-bg gap-3 text-vp-text-1 shrink-0">Nav</header>';
        $normalized = GrapesJsPastedComponentNormalizer::normalize($raw);

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, (string) $normalized['html'], '');

        $this->assertNotContains('vp:flex', $report['review']);
        $this->assertNotContains('bg-vp-bg', $report['review']);
        $this->assertNotContains('text-vp-text-1', $report['review']);
        $this->assertNotContains('shrink-0', $report['review']);
        $this->assertGreaterThan(0, $report['totals']['theme_ready']);
    }

    public function test_compiles_standard_utilities_in_jit_css(): void
    {
        $raw = '<header class="hidden gap-3 relative">Nav</header>';
        $normalized = GrapesJsPastedComponentNormalizer::normalize($raw);
        $css = GrapesJsPastedComponentNormalizer::compileTailwindCss((string) $normalized['html']);

        if ($css === '') {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, (string) $normalized['html'], $css);

        $this->assertNotContains('hidden', $report['review']);
        $this->assertNotContains('gap-3', $report['review']);
        $this->assertContains($report['status'], ['excellent', 'good']);
    }

    public function test_detects_chrome_block_context_and_boosts_status_for_theme_only_review(): void
    {
        $raw = '<div data-voodbuilder-block="site_nav_simple" class="hidden max-vp:flex bg-vp-bg-alt vp:gap-3">Nav</div>';
        $normalized = GrapesJsPastedComponentNormalizer::normalize($raw);
        $css = GrapesJsPastedComponentNormalizer::compileTailwindCss((string) $normalized['html']);

        if ($css === '') {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, (string) $normalized['html'], $css);

        $this->assertTrue($report['context']['chrome_block']);
        $this->assertSame('site_nav_simple', $report['context']['block_id']);
        $this->assertNotContains('max-vp:flex', $report['review']);
        $this->assertNotContains('bg-vp-bg-alt', $report['review']);
        $this->assertContains($report['status'], ['excellent', 'good']);
    }

    public function test_chrome_footer_block_gets_context(): void
    {
        $raw = '<footer data-voodbuilder-block="site_footer_centered" class="bg-vp-bg text-vp-text-2">Footer</footer>';
        $normalized = GrapesJsPastedComponentNormalizer::normalize($raw);

        $report = GrapesJsImportCompatibilityAnalyzer::analyze($raw, (string) $normalized['html'], '');

        $this->assertTrue($report['context']['chrome_block']);
        $this->assertSame('site_footer_centered', $report['context']['block_id']);
    }
}
