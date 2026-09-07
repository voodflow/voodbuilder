<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Tests\TestCase;

class PageBuilderTest extends TestCase
{
    public function test_matches_enum_instance(): void
    {
        $this->assertTrue(PageBuilder::matches(PageBuilder::Visual, PageBuilder::Visual));
        $this->assertFalse(PageBuilder::matches(PageBuilder::Visual, PageBuilder::RichEditor));
    }

    public function test_matches_string_value(): void
    {
        $this->assertTrue(PageBuilder::matches('visual', PageBuilder::Visual));
        $this->assertTrue(PageBuilder::matches('rich_editor', PageBuilder::RichEditor));
    }

    public function test_matches_legacy_grapesjs_value(): void
    {
        $this->assertTrue(PageBuilder::matches('grapesjs', PageBuilder::Visual));
        $this->assertSame(PageBuilder::Visual, PageBuilder::normalize('grapesjs'));
    }

    public function test_blank_state_defaults_to_visual(): void
    {
        $this->assertTrue(PageBuilder::matches(null, PageBuilder::Visual));
        $this->assertFalse(PageBuilder::matches(null, PageBuilder::RichEditor));
    }
}
