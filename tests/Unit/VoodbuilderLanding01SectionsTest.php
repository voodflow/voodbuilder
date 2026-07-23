<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderLanding01Sections;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderLanding01SectionsTest extends TestCase
{
    public function test_page_html_uses_theme_tokens_and_clean_copy(): void
    {
        $html = VoodbuilderLanding01Sections::pageHtml();

        $this->assertSame('Landing 01', VoodbuilderLanding01Sections::TEMPLATE_NAME);
        $this->assertStringContainsString('VoodBuilder', $html);
        $this->assertStringContainsString('bg-vp-bg', $html);
        $this->assertStringContainsString('text-vp-brand-1', $html);
        $this->assertStringContainsString('vb-landing01-hero', $html);
        $this->assertStringNotContainsString('GrapesJS', $html);
        $this->assertStringNotContainsString('dark:', $html);
        $this->assertStringNotContainsString('Astrolus', $html);
        $this->assertStringNotContainsString('images.pexels.com', $html);
    }

    public function test_register_blocks_uses_generic_categories(): void
    {
        VoodbuilderLanding01Sections::registerBlocks();

        $blocks = app(GrapesJsBlockRegistry::class)->toEditorBlocks();
        $byId = collect($blocks)->keyBy('id');

        $this->assertSame('Hero', $byId['vb-landing01-hero']['category']);
        $this->assertSame('Features', $byId['vb-landing01-features']['category']);
        $this->assertSame('CTA', $byId['vb-landing01-cta']['category']);
        $this->assertSame('Gradient hero', $byId['vb-landing01-hero']['label']);
    }
}
