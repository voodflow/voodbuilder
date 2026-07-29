<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderLanding03Sections;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderLanding03SectionsTest extends TestCase
{
    public function test_page_html_uses_theme_tokens_and_clean_copy(): void
    {
        $html = VoodbuilderLanding03Sections::pageHtml();

        $this->assertSame('Landing 03', VoodbuilderLanding03Sections::TEMPLATE_NAME);
        $this->assertStringContainsString('VoodBuilder', $html);
        $this->assertStringContainsString('bg-vp-bg', $html);
        $this->assertStringContainsString('text-vp-brand-1', $html);
        $this->assertStringContainsString('vb-nasa-hero', $html);
        $this->assertStringContainsString('vb-nasa-missions', $html);
        $this->assertStringContainsString('voodbuilder-hero-media__img', $html);
        $this->assertStringContainsString('data-voodbuilder-dropzone="actions"', $html);
        $this->assertStringContainsString('data-voodbuilder-dropzone="copy"', $html);
        $this->assertStringNotContainsString('data-voodbuilder-dropzone="content"', $html);
        $this->assertStringNotContainsString('GrapesJS', $html);
        $this->assertStringNotContainsString('grapesjs', strtolower($html));
        $this->assertStringNotContainsString('dark:', $html);
        $this->assertStringNotContainsString('jpl.nasa.gov', $html);
        $this->assertStringNotContainsString('images.pexels.com', $html);
    }

    public function test_register_blocks_uses_generic_categories(): void
    {
        VoodbuilderLanding03Sections::registerBlocks();

        $blocks = app(EditorBlockRegistry::class)->toEditorBlocks();
        $byId = collect($blocks)->keyBy('id');

        $this->assertSame('Hero', $byId['vb-nasa-hero']['category']);
        $this->assertSame('Stats', $byId['vb-nasa-stats']['category']);
        $this->assertSame('Gallery', $byId['vb-nasa-missions']['category']);
        $this->assertSame('Cinematic hero', $byId['vb-nasa-hero']['label']);
    }
}
