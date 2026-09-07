<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\StarterPageTemplates;
use Voodflow\Voodbuilder\Tests\TestCase;

class StarterPageTemplatesTest extends TestCase
{
    public function test_definitions_include_only_two_landing_templates(): void
    {
        $definitions = StarterPageTemplates::definitions();

        $this->assertCount(2, $definitions);
        $this->assertSame(['Landing 01', 'Landing 02'], array_column($definitions, 'name'));
        $this->assertSame(['Landing pages', 'Landing pages'], array_column($definitions, 'category'));

        foreach ($definitions as $definition) {
            $this->assertStringNotContainsString('GrapesJS', $definition['html']);
            $this->assertStringNotContainsString('grapesjs', strtolower($definition['html']));
            $this->assertStringNotContainsString('site_nav_simple', $definition['html']);
            $this->assertStringNotContainsString('images.pexels.com', $definition['html']);
            $this->assertTrue(
                str_contains($definition['html'], 'bg-vp-') || str_contains($definition['html'], 'text-vp-'),
                'Expected theme tokens in '.$definition['name'],
            );
        }

        $landing01 = $definitions[0];
        $this->assertStringContainsString('vb-landing01-articles', $landing01['html']);
        $this->assertStringContainsString('vb-landing01-cta', $landing01['html']);
        $this->assertStringContainsString('text-vp-brand-1', $landing01['html']);
        $this->assertStringNotContainsString('vb-landing01-hero', $landing01['html']);

        $landing02 = $definitions[1];
        $this->assertStringContainsString('vb-landing02-faq', $landing02['html']);
        $this->assertStringContainsString('<details', $landing02['html']);
        $this->assertStringNotContainsString('vb-landing02-hero', $landing02['html']);
    }
}
