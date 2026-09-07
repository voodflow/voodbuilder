<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderLanding02Sections;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderLanding02SectionsTest extends TestCase
{
    public function test_page_html_uses_theme_tokens_and_clean_copy(): void
    {
        $html = VoodbuilderLanding02Sections::pageHtml();

        $this->assertSame('Landing 02', VoodbuilderLanding02Sections::TEMPLATE_NAME);
        $this->assertStringContainsString('VoodBuilder', $html);
        $this->assertStringContainsString('bg-vp-bg-alt', $html);
        $this->assertStringContainsString('text-vp-text-1', $html);
        $this->assertStringContainsString('vb-landing02-faq', $html);
        $this->assertStringContainsString('<details', $html);
        $this->assertStringNotContainsString('vb-landing02-hero', $html);
        $this->assertStringNotContainsString('GrapesJS', $html);
        $this->assertStringNotContainsString('grapesjs', strtolower($html));
        $this->assertStringNotContainsString('dark:', $html);
        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringNotContainsString('Daiva', $html);
        $this->assertStringNotContainsString('images.pexels.com', $html);
    }

    public function test_register_blocks_uses_generic_categories(): void
    {
        VoodbuilderLanding02Sections::registerBlocks();

        $blocks = app(EditorBlockRegistry::class)->toEditorBlocks();
        $byId = collect($blocks)->keyBy('id');

        $this->assertSame('Content', $byId['vb-landing02-faq']['category']);
        $this->assertSame('FAQ accordion', $byId['vb-landing02-faq']['label']);
        $this->assertArrayNotHasKey('vb-landing02-hero', $byId->all());
    }
}
