<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;
use Voodflow\Voodbuilder\Support\GrapesJs\ChromeLayoutContentSlotBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutDefaultsTest extends TestCase
{
    public function test_starter_html_includes_content_slot(): void
    {
        $html = ChromeLayoutDefaults::starterHtml();

        $this->assertStringContainsString('data-voodbuilder-content-slot="main"', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome-drop-zone="nav"', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome-drop-zone="footer"', $html);
        $this->assertStringContainsString('data-gjs-type="voodbuilder-chrome-drop-zone"', $html);
        $this->assertStringNotContainsString('site_header', $html);
        $this->assertStringNotContainsString('site_footer_columns_simple', $html);
    }

    public function test_content_slot_block_uses_shared_markup(): void
    {
        $definition = ChromeLayoutContentSlotBlock::definition();

        $this->assertSame('chrome_content_slot', $definition->id);
        $this->assertSame(ChromeLayoutDefaults::contentSlotHtml(), $definition->content);
        $this->assertSame('chrome_layout', $definition->attributes['data-voodbuilder-editor-scope']);
    }
}
