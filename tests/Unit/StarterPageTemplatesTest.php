<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\StarterPageTemplates;
use Voodflow\Voodbuilder\Tests\TestCase;

class StarterPageTemplatesTest extends TestCase
{
    public function test_definitions_include_only_landing_01_community_starter(): void
    {
        $definitions = StarterPageTemplates::definitions();

        $this->assertCount(1, $definitions);
        $this->assertSame(['Landing 01'], array_column($definitions, 'name'));
        $this->assertSame(['Landing pages'], array_column($definitions, 'category'));
        $this->assertSame(['Landing 01'], StarterPageTemplates::keepNames());

        $landing01 = $definitions[0];
        $this->assertStringNotContainsString('GrapesJS', $landing01['html']);
        $this->assertStringNotContainsString('grapesjs', strtolower($landing01['html']));
        $this->assertStringNotContainsString('site_nav_simple', $landing01['html']);
        $this->assertStringNotContainsString('images.pexels.com', $landing01['html']);
        $this->assertTrue(
            str_contains($landing01['html'], 'bg-vp-') || str_contains($landing01['html'], 'text-vp-'),
            'Expected theme tokens in Landing 01',
        );
        $this->assertStringContainsString('vb-landing01-articles', $landing01['html']);
        $this->assertStringContainsString('vb-landing01-cta', $landing01['html']);
        $this->assertStringContainsString('text-vp-brand-1', $landing01['html']);
        $this->assertStringNotContainsString('vb-landing01-hero', $landing01['html']);
        $this->assertStringNotContainsString('vb-landing02-faq', $landing01['html']);
    }
}
