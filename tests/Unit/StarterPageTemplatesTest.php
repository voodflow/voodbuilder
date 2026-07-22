<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\StarterPageTemplates;
use Voodflow\Voodbuilder\Tests\TestCase;

class StarterPageTemplatesTest extends TestCase
{
    public function test_definitions_include_six_starter_templates(): void
    {
        $definitions = StarterPageTemplates::definitions();

        $this->assertCount(6, $definitions);
        $this->assertSame('Auto parts megastore', $definitions[0]['name']);
        $this->assertSame('Ecommerce', $definitions[0]['category']);
        $this->assertStringNotContainsString('site_nav_simple', $definitions[0]['html']);
        $this->assertStringNotContainsString('site_footer_columns_simple', $definitions[0]['html']);
        $this->assertStringContainsString('alt="ecommerce"', $definitions[0]['html']);
        $this->assertStringNotContainsString('images.pexels.com', $definitions[0]['html']);
        $this->assertStringContainsString('bg-primary', $definitions[0]['html']);

        $growth = collect($definitions)->firstWhere('name', 'Growth campaign');
        $this->assertNotNull($growth);
        $this->assertStringContainsString('Turn traffic into qualified pipeline', $growth['html']);
        $this->assertStringNotContainsString('images.pexels.com', $growth['html']);
        $this->assertStringNotContainsString('site_nav_simple', $growth['html']);
        $this->assertLessThan(10000, strlen($growth['html']));
    }
}
