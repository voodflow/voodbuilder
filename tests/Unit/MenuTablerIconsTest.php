<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\MenuTablerIcons;
use Voodflow\Voodbuilder\Tests\TestCase;

class MenuTablerIconsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MenuTablerIcons::flushCatalogCache();
    }

    public function test_parse_outline_and_filled_keys(): void
    {
        $this->assertSame(
            ['name' => 'home', 'style' => MenuTablerIcons::STYLE_OUTLINE],
            MenuTablerIcons::parse('home'),
        );
        $this->assertSame(
            ['name' => 'home', 'style' => MenuTablerIcons::STYLE_FILLED],
            MenuTablerIcons::parse('home:filled'),
        );
        $this->assertSame('home:filled', MenuTablerIcons::key('home', MenuTablerIcons::STYLE_FILLED));
    }

    public function test_search_results_include_preview_html_and_filled_variant(): void
    {
        $results = MenuTablerIcons::searchResults('home', 20);

        $this->assertArrayHasKey('home', $results);
        $this->assertStringContainsString('<svg', $results['home']);
        $this->assertStringContainsString('outline', strtolower(strip_tags($results['home'])));

        if (MenuTablerIcons::has('home:filled')) {
            $this->assertArrayHasKey('home:filled', $results);
            $this->assertStringContainsString('filled', strtolower(strip_tags($results['home:filled'])));
            $this->assertStringContainsString('fill="currentColor"', $results['home:filled']);
        }
    }

    public function test_svg_html_renders_outline_stroke_and_filled_fill(): void
    {
        $outline = MenuTablerIcons::svgHtml('home');
        $this->assertStringContainsString('stroke="currentColor"', $outline);
        $this->assertStringContainsString('fill="none"', $outline);

        if (MenuTablerIcons::has('home:filled')) {
            $filled = MenuTablerIcons::svgHtml('home:filled');
            $this->assertStringContainsString('fill="currentColor"', $filled);
            $this->assertStringContainsString('stroke="none"', $filled);
        }
    }

    public function test_legacy_social_icons_still_resolve(): void
    {
        $this->assertTrue(MenuTablerIcons::has('brand-facebook'));
        $this->assertNotNull(MenuTablerIcons::path('brand-facebook'));
    }
}
