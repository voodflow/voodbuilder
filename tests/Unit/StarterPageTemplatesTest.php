<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\StarterPageTemplates;
use Voodflow\Voodbuilder\Tests\TestCase;

class StarterPageTemplatesTest extends TestCase
{
    public function test_definitions_include_five_starter_templates(): void
    {
        $definitions = StarterPageTemplates::definitions();

        $this->assertCount(6, $definitions);
        $this->assertSame('Auto parts megastore', $definitions[0]['name']);
        $this->assertSame('Ecommerce', $definitions[0]['category']);
        $this->assertStringContainsString('site_nav_simple', $definitions[0]['html']);
        $this->assertStringContainsString('site_footer_columns_simple', $definitions[0]['html']);
        $this->assertStringContainsString('images.pexels.com', $definitions[0]['html']);
        $this->assertStringContainsString('bg-primary', $definitions[0]['html']);
    }
}
