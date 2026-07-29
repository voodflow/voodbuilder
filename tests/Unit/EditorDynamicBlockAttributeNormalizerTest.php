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

    public function test_fills_default_config_for_latest_vtuts_when_missing(): void
    {
        $broken = '<div data-voodbuilder-block="latest_vtuts" data-voodbuilder-config="{" class="voodbuilder-editor-dynamic"></div>';

        $normalized = EditorDynamicBlockAttributeNormalizer::normalize($broken);

        $this->assertStringContainsString('data-voodbuilder-block="latest_vtuts"', $normalized);

        preg_match('/data-voodbuilder-config=(["\'])(.+?)\1/', $normalized, $matches);

        $config = EditorDynamicBlockAttributeNormalizer::decodeConfig($matches[2] ?? '');

        $this->assertSame(6, $config['limit'] ?? null);
        $this->assertSame(3, $config['columns'] ?? null);
    }
}
