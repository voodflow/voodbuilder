<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderLanding01Sections;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderLanding01SectionsTest extends TestCase
{
    public function test_page_html_uses_theme_tokens_and_voodbuilder_copy(): void
    {
        $html = VoodbuilderLanding01Sections::pageHtml();

        $this->assertStringContainsString('VoodBuilder', $html);
        $this->assertStringContainsString('bg-vp-bg', $html);
        $this->assertStringContainsString('text-vp-brand-1', $html);
        $this->assertStringContainsString('vb-landing01-hero', $html);
        $this->assertStringNotContainsString('dark:', $html);
        $this->assertStringNotContainsString('Astrolus', $html);
        $this->assertStringNotContainsString('images.pexels.com', $html);
    }

    public function test_register_blocks_adds_landing_01_sections_to_registry(): void
    {
        VoodbuilderLanding01Sections::registerBlocks();

        $blocks = app(GrapesJsBlockRegistry::class)->toEditorBlocks();
        $ids = collect($blocks)->pluck('id')->all();

        $this->assertContains('vb-landing01-hero', $ids);
        $this->assertContains('vb-landing01-features', $ids);
        $this->assertContains('vb-landing01-cta', $ids);

        $hero = collect($blocks)->firstWhere('id', 'vb-landing01-hero');
        $this->assertNotNull($hero);
        $this->assertSame('Voodbuilder / Landing 01', $hero['category']);
    }
}
