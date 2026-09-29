<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit\SiteVisit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Voodflow\Voodbuilder\Support\SiteVisit\SiteVisitMatcher;
use Voodflow\Voodbuilder\Support\SiteVisit\SiteVisitPath;

final class SiteVisitMatcherTest extends TestCase
{
    #[Test]
    public function it_normalizes_paths(): void
    {
        $this->assertSame('/', SiteVisitPath::normalize(''));
        $this->assertSame('/', SiteVisitPath::normalize('/'));
        $this->assertSame('/courses/laravel', SiteVisitPath::normalize('/courses/laravel/'));
        $this->assertSame('/courses/laravel', SiteVisitPath::fromUrl('https://example.test/courses/laravel?x=1'));
    }

    #[Test]
    public function it_matches_any(): void
    {
        $this->assertTrue(SiteVisitMatcher::matches(
            ['match_type' => 'any'],
            ['path' => '/anything'],
        ));
    }

    #[Test]
    public function it_matches_custom_path(): void
    {
        $this->assertTrue(SiteVisitMatcher::matches(
            ['match_type' => 'path', 'path' => '/courses/laravel'],
            ['path' => '/courses/laravel/'],
        ));

        $this->assertFalse(SiteVisitMatcher::matches(
            ['match_type' => 'path', 'path' => '/courses/laravel'],
            ['path' => '/courses/other'],
        ));
    }

    #[Test]
    public function it_matches_page_by_id_from_visit(): void
    {
        $this->assertTrue(SiteVisitMatcher::matches(
            ['match_type' => 'page', 'page_id' => '42'],
            ['path' => '/somewhere', 'page_id' => '42'],
        ));

        $this->assertFalse(SiteVisitMatcher::matches(
            ['match_type' => 'page', 'page_id' => '42'],
            ['path' => '/somewhere', 'page_id' => '99'],
        ));
    }

    #[Test]
    public function it_requires_target_for_page_and_menu(): void
    {
        $this->assertFalse(SiteVisitMatcher::matches(
            ['match_type' => 'page', 'page_id' => ''],
            ['path' => '/x'],
        ));

        $this->assertFalse(SiteVisitMatcher::matches(
            ['match_type' => 'menu_item', 'menu_item_id' => ''],
            ['path' => '/x'],
        ));
    }
}
