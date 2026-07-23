<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderLanding02Sections;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderLanding02SectionsTest extends TestCase
{
    public function test_page_html_uses_theme_tokens_and_clean_copy(): void
    {
        $html = VoodbuilderLanding02Sections::pageHtml();

        $this->assertSame('Landing 02', VoodbuilderLanding02Sections::TEMPLATE_NAME);
        $this->assertStringContainsString('VoodBuilder', $html);
        $this->assertStringContainsString('bg-vp-bg', $html);
        $this->assertStringContainsString('text-vp-brand-1', $html);
        $this->assertStringContainsString('vb-landing02-hero', $html);
        $this->assertStringContainsString('<details', $html);
        $this->assertStringNotContainsString('GrapesJS', $html);
        $this->assertStringNotContainsString('dark:', $html);
        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringNotContainsString('Daiva', $html);
        $this->assertStringNotContainsString('images.pexels.com', $html);
    }

    public function test_register_blocks_uses_generic_categories(): void
    {
        VoodbuilderLanding02Sections::registerBlocks();

        $blocks = app(GrapesJsBlockRegistry::class)->toEditorBlocks();
        $byId = collect($blocks)->keyBy('id');

        $this->assertSame('Hero', $byId['vb-landing02-hero']['category']);
        $this->assertSame('Features', $byId['vb-landing02-features']['category']);
        $this->assertSame('Content', $byId['vb-landing02-faq']['category']);
        $this->assertSame('Editorial hero', $byId['vb-landing02-hero']['label']);
    }
}
