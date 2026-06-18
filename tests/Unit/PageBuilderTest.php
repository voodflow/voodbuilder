<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Enums\PageBuilder;
use Voodflow\Vpress\Tests\TestCase;

class PageBuilderTest extends TestCase
{
    public function test_matches_enum_instance(): void
    {
        $this->assertTrue(PageBuilder::matches(PageBuilder::GrapesJs, PageBuilder::GrapesJs));
        $this->assertFalse(PageBuilder::matches(PageBuilder::GrapesJs, PageBuilder::RichEditor));
    }

    public function test_matches_string_value(): void
    {
        $this->assertTrue(PageBuilder::matches('grapesjs', PageBuilder::GrapesJs));
        $this->assertTrue(PageBuilder::matches('rich_editor', PageBuilder::RichEditor));
    }

    public function test_blank_state_defaults_to_rich_editor(): void
    {
        $this->assertTrue(PageBuilder::matches(null, PageBuilder::RichEditor));
    }
}
