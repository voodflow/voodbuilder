<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockAttributeNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorDynamicBlockAttributeNormalizerTest extends TestCase
{
    public function test_normalizes_broken_editor_config_attributes(): void
    {
        $broken = <<<'HTML'
<div data-voodbuilder-block="site_footer" data-voodbuilder-config="{" menu":"footer"}"="" class="voodbuilder-editor-dynamic"></div>
HTML;

        $normalized = EditorDynamicBlockAttributeNormalizer::normalize($broken);

        $this->assertStringContainsString('data-voodbuilder-block="site_footer"', $normalized);
        $this->assertStringNotContainsString(' menu":"footer"}"=""', $normalized);
        $this->assertStringNotContainsString(' menu=', $normalized);

        preg_match('/data-voodbuilder-config=(["\'])(.+?)\1/', $normalized, $matches);

        $this->assertSame(
            ['menu' => 'footer'],
            EditorDynamicBlockAttributeNormalizer::decodeConfig($matches[2] ?? ''),
        );
    }

    public function test_fills_default_config_for_registered_block_when_missing(): void
    {
        $broken = '<div data-voodbuilder-block="landing_stats" data-voodbuilder-config="{" class="voodbuilder-editor-dynamic"></div>';

        $normalized = EditorDynamicBlockAttributeNormalizer::normalize($broken);

        $this->assertStringContainsString('data-voodbuilder-block="landing_stats"', $normalized);

        preg_match('/data-voodbuilder-config=(["\'])(.+?)\1/', $normalized, $matches);

        $config = EditorDynamicBlockAttributeNormalizer::decodeConfig($matches[2] ?? '');

        $this->assertIsArray($config['items'] ?? null);
        $this->assertCount(3, $config['items']);
    }

    public function test_keeps_the_author_content_width_measure_on_the_dynamic_block_root(): void
    {
        // 1.11.79: a dynamic block owns its measure on the root (no content shell
        // inside the mount). The save normalizer must not prune the width attr, or
        // the author's choice silently disappears on save. The corrupted config
        // attribute (`limit=""`) is still pruned.
        $saved = <<<'HTML'
<section data-voodbuilder-block="demo_pages_explorer" data-voodbuilder-config='{"heading":"Explore"}' data-voodbuilder-content-width="normal" class="voodbuilder-editor-dynamic mx-auto w-full max-w-[80rem]" limit=""></section>
HTML;

        $normalized = EditorDynamicBlockAttributeNormalizer::normalize($saved);

        $this->assertStringContainsString('data-voodbuilder-content-width="normal"', $normalized);
        $this->assertStringContainsString('max-w-[80rem]', $normalized);
        $this->assertStringNotContainsString('limit=', $normalized);
    }
}
