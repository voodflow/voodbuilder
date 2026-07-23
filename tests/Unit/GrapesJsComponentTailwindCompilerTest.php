<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentTailwindCompiler;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsComponentTailwindCompilerTest extends TestCase
{
    public function test_compiles_prose_and_typography_utilities(): void
    {
        if (GrapesJsComponentTailwindCompiler::compile('<span class="text-sm">probe</span>') === null) {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $html = <<<'HTML'
            <article class="prose prose-xl mx-auto">
                <h1 class="text-4xl font-serif tracking-tight text-gray-900">Title</h1>
                <p class="text-gray-600">Body copy</p>
            </article>
        HTML;

        $css = GrapesJsPastedComponentNormalizer::compileTailwindCss($html);

        $this->assertNotSame('', $css);
        $this->assertStringContainsString('.voodbuilder-pasted-component .prose', $css);
        $this->assertStringContainsString('.voodbuilder-pasted-component .text-4xl', $css);
    }

    public function test_preserves_class_based_dark_variant_selectors(): void
    {
        if (GrapesJsComponentTailwindCompiler::compile('<span class="text-sm">probe</span>') === null) {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $css = GrapesJsPastedComponentNormalizer::compilePageTailwindCss(
            '<div class="bg-blue-200 dark:bg-red-200">Popup</div>',
        );

        $this->assertStringContainsString('.bg-blue-200', $css);
        $this->assertStringContainsString('.dark\\:bg-red-200:is(.dark, .dark *)', $css);
        $this->assertStringNotContainsString('.dark\\:bg-red-200 .dark *', $css);
        $this->assertLessThan(
            strpos($css, '.dark\\:bg-red-200:is(.dark, .dark *)'),
            strpos($css, '.bg-blue-200'),
        );
    }

    public function test_dark_background_variant_beats_base_background_utility(): void
    {
        if (GrapesJsComponentTailwindCompiler::compile('<span class="text-sm">probe</span>') === null) {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $css = GrapesJsPastedComponentNormalizer::compilePageTailwindCss(
            '<div class="dark:bg-red-200 bg-blue-200">Popup</div>',
        );

        $this->assertMatchesRegularExpression(
            '/\.dark\\\\:bg-red-200:is\(\.dark,\s*\.dark \*\)\s*\{[^}]*background-color:\s*var\(--color-red-200\)/',
            $css,
        );
    }
}
