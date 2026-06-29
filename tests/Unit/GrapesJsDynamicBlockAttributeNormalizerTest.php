<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockAttributeNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsDynamicBlockAttributeNormalizerTest extends TestCase
{
    public function test_normalizes_broken_grapesjs_config_attributes(): void
    {
        $broken = <<<'HTML'
<div data-voodbuilder-block="site_footer" data-voodbuilder-config="{" menu":"footer"}"="" class="voodbuilder-gjs-dynamic"></div>
HTML;

        $normalized = GrapesJsDynamicBlockAttributeNormalizer::normalize($broken);

        $this->assertStringContainsString('data-voodbuilder-block="site_footer"', $normalized);
        $this->assertStringNotContainsString(' menu":"footer"}"=""', $normalized);
        $this->assertStringNotContainsString(' menu=', $normalized);

        preg_match('/data-voodbuilder-config=\'([^\']+)\'/', $normalized, $matches);

        if ($matches === []) {
            preg_match('/data-voodbuilder-config="([^"]+)"/', $normalized, $matches);
        }

        $this->assertSame(
            ['menu' => 'footer'],
            GrapesJsDynamicBlockAttributeNormalizer::decodeConfig($matches[1] ?? ''),
        );
    }

    public function test_fills_default_config_for_latest_vtuts_when_missing(): void
    {
        $broken = '<div data-voodbuilder-block="latest_vtuts" data-voodbuilder-config="{" class="voodbuilder-gjs-dynamic"></div>';

        $normalized = GrapesJsDynamicBlockAttributeNormalizer::normalize($broken);

        $this->assertStringContainsString('data-voodbuilder-block="latest_vtuts"', $normalized);

        preg_match('/data-voodbuilder-config="([^"]+)"/', $normalized, $matches);

        $config = GrapesJsDynamicBlockAttributeNormalizer::decodeConfig($matches[1] ?? '');

        $this->assertSame(6, $config['limit'] ?? null);
        $this->assertSame(3, $config['columns'] ?? null);
    }
}
