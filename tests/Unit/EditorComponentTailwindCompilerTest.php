<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorComponentTailwindCompiler;
use Voodflow\Voodbuilder\Support\Editor\EditorPastedComponentNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorComponentTailwindCompilerTest extends TestCase
{
    public function test_compiles_prose_and_typography_utilities(): void
    {
        if (EditorComponentTailwindCompiler::compile('<span class="text-sm">probe</span>') === null) {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $html = <<<'HTML'
            <article class="prose prose-xl mx-auto">
                <h1 class="text-4xl font-serif tracking-tight text-gray-900">Title</h1>
                <p class="text-gray-600">Body copy</p>
            </article>
        HTML;

        $css = EditorPastedComponentNormalizer::compileTailwindCss($html);

        $this->assertNotSame('', $css);
        $this->assertStringContainsString('.voodbuilder-pasted-component .prose', $css);
        $this->assertStringContainsString('.voodbuilder-pasted-component .text-4xl', $css);
    }

    public function test_preserves_class_based_dark_variant_selectors(): void
    {
        if (EditorComponentTailwindCompiler::compile('<span class="text-sm">probe</span>') === null) {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $css = EditorPastedComponentNormalizer::compilePageTailwindCss(
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
        if (EditorComponentTailwindCompiler::compile('<span class="text-sm">probe</span>') === null) {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $css = EditorPastedComponentNormalizer::compilePageTailwindCss(
            '<div class="dark:bg-red-200 bg-blue-200">Popup</div>',
        );

        $this->assertMatchesRegularExpression(
            '/\.dark\\\\:bg-red-200:is\(\.dark,\s*\.dark \*\)\s*\{[^}]*background-color:\s*var\(--color-red-200\)/',
            $css,
        );
    }

    public function test_page_compile_keeps_literal_palette_color_variables(): void
    {
        if (EditorComponentTailwindCompiler::compile('<span class="text-sm">probe</span>') === null) {
            $this->markTestSkipped('Node.js Tailwind compiler is not available in this environment.');
        }

        $css = EditorPastedComponentNormalizer::compilePageTailwindCss(
            '<div class="text-violet-400/10 text-indigo-400/10 text-sky-400/10">Watermark</div>',
        );

        $this->assertMatchesRegularExpression('/--color-violet-400\s*:/', $css);
        $this->assertMatchesRegularExpression('/--color-indigo-400\s*:/', $css);
        $this->assertMatchesRegularExpression('/--color-sky-400\s*:/', $css);
        $this->assertStringContainsString('.text-violet-400\\/10', $css);
        $this->assertStringNotContainsString('var(--color-vp-brand', $css);
    }
}
