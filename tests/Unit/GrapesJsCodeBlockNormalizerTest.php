<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Spatie\LaravelMarkdown\MarkdownServiceProvider;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsCodeBlockNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsCodeBlockNormalizerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            MarkdownServiceProvider::class,
        ];
    }

    public function test_highlights_plain_vp_code_blocks_with_shiki(): void
    {
        $html = <<<'HTML'
<div class="vp-code-block" data-code-block data-line-numbers>
    <div class="vp-code-block__header">
        <span class="vp-code-block__lang">PHP</span>
        <button type="button" class="vp-code-block__copy" data-code-copy>Copy</button>
    </div>
    <div class="vp-code-block__body">
        <pre><code class="language-php">&lt;?php echo 'hi';</code></pre>
    </div>
</div>
HTML;

        $normalized = GrapesJsCodeBlockNormalizer::normalize($html);

        $this->assertStringContainsString('vp-code-block', $normalized);
        $this->assertStringContainsString('data-code-copy', $normalized);
        $this->assertStringContainsString('shiki', $normalized);
        $this->assertStringContainsString('echo', $normalized);
        $this->assertStringNotContainsString('&lt;?php echo', $normalized);
    }

    public function test_dedupes_nested_vp_code_blocks(): void
    {
        $nested = <<<'HTML'
<div class="vp-code-block" data-code-block>
    <div class="vp-code-block__header"><span class="vp-code-block__lang">PHP</span></div>
    <div class="vp-code-block__body">
        <div class="vp-code-block" data-code-block>
            <div class="vp-code-block__header"><span class="vp-code-block__lang">CODE</span></div>
            <div class="vp-code-block__body">
                <pre class="shiki"><code>echo 1;</code></pre>
            </div>
        </div>
    </div>
</div>
HTML;

        $normalized = GrapesJsCodeBlockNormalizer::normalize($nested);

        $this->assertSame(1, substr_count($normalized, 'class="vp-code-block"'));
        $this->assertStringContainsString('echo 1;', $normalized);
    }
}
