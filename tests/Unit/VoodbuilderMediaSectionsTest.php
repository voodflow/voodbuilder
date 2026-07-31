<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderMediaSections;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderMediaSectionsTest extends TestCase
{
    public function test_block_definitions_use_theme_tokens_and_dropzones(): void
    {
        foreach (VoodbuilderMediaSections::blockDefinitions() as $definition) {
            $html = $definition['content'];

            $this->assertStringContainsString('data-voodbuilder-section-block="'.$definition['id'].'"', $html);
            $this->assertMatchesRegularExpression('/vp-/', $html);
            $this->assertStringNotContainsString('GrapesJS', $html);
            $this->assertStringNotContainsString('grapesjs', strtolower($html));

            if ($definition['category'] === 'Hero') {
                $this->assertStringContainsString('data-voodbuilder-dropzone=', $html);
            }
        }
    }

    public function test_background_image_hero_uses_cover_media_layers(): void
    {
        $html = VoodbuilderMediaSections::backgroundImage();

        $this->assertStringContainsString('data-voodbuilder-role="media"', $html);
        $this->assertStringContainsString('data-voodbuilder-role="shade"', $html);
        $this->assertStringContainsString('data-voodbuilder-role="content"', $html);
        $this->assertStringContainsString('data-voodbuilder-dropzone="actions"', $html);
        $this->assertStringContainsString('data-voodbuilder-dropzone="copy"', $html);
        $this->assertStringNotContainsString('data-voodbuilder-dropzone="content"', $html);
        $this->assertStringContainsString('voodbuilder-hero-media__img', $html);
        $this->assertStringContainsString('data-vb-bg-size="cover"', $html);
        $this->assertStringContainsString('data-vb-min-height="70vh"', $html);
    }

    public function test_register_blocks_adds_media_sections_to_registry(): void
    {
        VoodbuilderMediaSections::registerBlocks();

        $blocks = app(EditorBlockRegistry::class)->toEditorBlocks();
        $ids = collect($blocks)->pluck('id')->all();

        $this->assertContains('vb-bg-image', $ids);
        $this->assertNotContains('vb-bg-video', $ids);
        $this->assertNotContains('vb-slider-images', $ids);
        $this->assertNotContains('vb-slider-videos', $ids);

        $hero = collect($blocks)->firstWhere('id', 'vb-bg-image');

        $this->assertNotNull($hero);
        $this->assertSame('Hero', $hero['category']);
        $this->assertSame('Background image', $hero['label']);
    }
}
