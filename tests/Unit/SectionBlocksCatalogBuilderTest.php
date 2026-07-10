<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBrandingNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\SectionBlocksCatalogBuilder;

class SectionBlocksCatalogBuilderTest extends TestCase
{
    public function test_build_applies_voodbuilder_branding_to_section_content(): void
    {
        if (! is_file(SectionBlocksCatalogBuilder::sourcePath())) {
            $this->markTestSkipped('section-source-blocks.json source missing.');
        }

        $blocks = (new SectionBlocksCatalogBuilder)->build();

        $this->assertNotEmpty($blocks);

        $footer = collect($blocks)->first(fn (array $block): bool => str_contains((string) ($block['id'] ?? ''), 'footer'));

        $this->assertNotNull($footer);
        $this->assertStringContainsString('VoodBuilder', (string) $footer['content']);
        $this->assertStringNotContainsString('Tailblocks', (string) $footer['content']);
        $this->assertStringContainsString('voodbuilder-brand-mark', (string) $footer['content']);
    }

    public function test_renderer_does_not_rewrite_tailblocks_branding_at_runtime(): void
    {
        $html = '<footer><span>Tailblocks</span><p>© 2020 Tailblocks</p></footer>';

        $page = new SitePage([
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => ['html' => $html, 'css' => '', 'js' => ''],
        ]);

        $rendered = (new GrapesJsRenderer)->html($page);

        $this->assertStringContainsString('Tailblocks', $rendered);
        $this->assertStringNotContainsString(GrapesJsBrandingNormalizer::BRAND_NAME, $rendered);
    }
}
