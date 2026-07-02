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

    public function test_css_includes_utility_detects_escaped_variants(): void
    {
        $css = '.voodbuilder-pasted-component .hover\\:bg-primary-hover:hover { background-color: red; }';

        $this->assertTrue(GrapesJsImportCompatibilityAnalyzer::cssIncludesUtility($css, 'hover:bg-primary-hover'));
    }
}
