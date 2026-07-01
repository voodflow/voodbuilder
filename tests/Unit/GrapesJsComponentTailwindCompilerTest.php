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
}
