<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderMediaSections;
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
        $this->assertStringContainsString('data-voodbuilder-dropzone="content"', $html);
        $this->assertStringContainsString('voodbuilder-hero-media__img', $html);
        $this->assertStringContainsString('data-vb-bg-size="cover"', $html);
        $this->assertStringContainsString('data-vb-min-height="70vh"', $html);
    }

    public function test_slider_blocks_use_scroll_snap_markup(): void
    {
        $images = VoodbuilderMediaSections::sliderImages();
        $videos = VoodbuilderMediaSections::sliderVideos();

        $this->assertStringContainsString('class="voodbuilder-slider', $images);
        $this->assertStringContainsString('data-voodbuilder-slider="images"', $images);
        $this->assertStringContainsString('voodbuilder-slider__track', $images);
        $this->assertStringContainsString('data-vb-slider-mode="static"', $images);

        $this->assertStringContainsString('data-voodbuilder-slider="videos"', $videos);
        $this->assertStringContainsString('voodbuilder-slider__slide', $videos);
    }

    public function test_register_blocks_adds_media_sections_to_registry(): void
    {
        VoodbuilderMediaSections::registerBlocks();

        $blocks = app(GrapesJsBlockRegistry::class)->toEditorBlocks();
        $ids = collect($blocks)->pluck('id')->all();

        $this->assertContains('vb-bg-image', $ids);
        $this->assertContains('vb-bg-video', $ids);
        $this->assertContains('vb-slider-images', $ids);
        $this->assertContains('vb-slider-videos', $ids);

        $hero = collect($blocks)->firstWhere('id', 'vb-bg-image');
        $gallery = collect($blocks)->firstWhere('id', 'vb-slider-images');

        $this->assertNotNull($hero);
        $this->assertNotNull($gallery);
        $this->assertSame('Hero', $hero['category']);
        $this->assertSame('Gallery', $gallery['category']);
        $this->assertSame('Background image', $hero['label']);
    }
}
