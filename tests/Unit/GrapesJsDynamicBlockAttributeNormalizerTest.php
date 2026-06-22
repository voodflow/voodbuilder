<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockAttributeNormalizer;
use Voodflow\Vpress\Tests\TestCase;

class GrapesJsDynamicBlockAttributeNormalizerTest extends TestCase
{
    public function test_normalizes_broken_grapesjs_config_attributes(): void
    {
        $broken = <<<'HTML'
<div data-vpress-block="site_footer" data-vpress-config="{" menu":"footer"}"="" class="vpress-gjs-dynamic"></div>
HTML;

        $normalized = GrapesJsDynamicBlockAttributeNormalizer::normalize($broken);

        $this->assertStringContainsString('data-vpress-block="site_footer"', $normalized);
        $this->assertStringNotContainsString(' menu":"footer"}"=""', $normalized);
        $this->assertStringNotContainsString(' menu=', $normalized);

        preg_match('/data-vpress-config=\'([^\']+)\'/', $normalized, $matches);

        if ($matches === []) {
            preg_match('/data-vpress-config="([^"]+)"/', $normalized, $matches);
        }

        $this->assertSame(
            ['menu' => 'footer'],
            GrapesJsDynamicBlockAttributeNormalizer::decodeConfig($matches[1] ?? ''),
        );
    }

    public function test_fills_default_config_for_latest_vtuts_when_missing(): void
    {
        $broken = '<div data-vpress-block="latest_vtuts" data-vpress-config="{" class="vpress-gjs-dynamic"></div>';

        $normalized = GrapesJsDynamicBlockAttributeNormalizer::normalize($broken);

        $this->assertStringContainsString('data-vpress-block="latest_vtuts"', $normalized);

        preg_match('/data-vpress-config="([^"]+)"/', $normalized, $matches);

        $config = GrapesJsDynamicBlockAttributeNormalizer::decodeConfig($matches[1] ?? '');

        $this->assertSame(6, $config['limit'] ?? null);
        $this->assertSame(3, $config['columns'] ?? null);
    }
}
