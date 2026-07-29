<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\StarterPageTemplates;
use Voodflow\Voodbuilder\Tests\TestCase;

class StarterPageTemplatesTest extends TestCase
{
    public function test_definitions_include_only_three_landing_templates(): void
    {
        $definitions = StarterPageTemplates::definitions();

        $this->assertCount(3, $definitions);
        $this->assertSame(['Landing 01', 'Landing 02', 'Landing 03'], array_column($definitions, 'name'));
        $this->assertSame(['Landing pages', 'Landing pages', 'Landing pages'], array_column($definitions, 'category'));

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
        $this->assertStringContainsString('Ship marketing pages with', $landing01['html']);
        $this->assertStringContainsString('text-vp-brand-1', $landing01['html']);

        $landing03 = $definitions[2];
        $this->assertStringContainsString('vb-nasa-hero', $landing03['html']);
        $this->assertStringContainsString('voodbuilder-hero-media__img', $landing03['html']);
        $this->assertStringContainsString('data-voodbuilder-dropzone="actions"', $landing03['html']);
        $this->assertStringContainsString('data-voodbuilder-dropzone="copy"', $landing03['html']);
        $this->assertStringNotContainsString('data-voodbuilder-dropzone="content"', $landing03['html']);
    }
}
