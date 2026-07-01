<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsStepTabsNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsStepTabsNormalizerTest extends TestCase
{
    public function test_upgrades_step_nav_to_accessible_tabs(): void
    {
        $html = <<<'HTML'
<section class="text-gray-600">
<div class="container flex flex-col">
<div class="flex mx-auto flex-wrap mb-20">
<a class="border-indigo-500 text-indigo-500" href="#">STEP 1</a>
<a class="border-gray-200" href="#">STEP 2</a>
<a class="border-gray-200" href="#">STEP 3</a>
</div>
<img alt="hero" src="/img.png" />
<div class="flex flex-col"><h1>Title</h1><p>Body</p></div>
</div>
</section>
HTML;

        $normalized = GrapesJsStepTabsNormalizer::normalize($html);

        $this->assertStringContainsString('role="tablist"', $normalized);
        $this->assertStringContainsString('role="tab"', $normalized);
        $this->assertStringContainsString('role="tabpanel"', $normalized);
        $this->assertStringContainsString('aria-controls', $normalized);
        $this->assertStringContainsString('data-voodbuilder-step-tabs', $normalized);
        $this->assertStringContainsString('vb-step-tabs__tab--active', $normalized);
        $this->assertSame(3, substr_count($normalized, 'role="tabpanel"'));
    }
}
