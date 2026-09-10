<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SearchSettings;
use Voodflow\Voodbuilder\Support\SiteSearch;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        VoodbuilderSettings::query()->delete();
        VoodbuilderSettings::clearCache();
    }

    #[Test]
    public function it_clamps_search_settings_from_storage(): void
    {
        VoodbuilderSettings::saveData([
            'search_per_page' => 999,
            'search_per_type' => 2,
            'search_snippet_length' => 40,
        ]);

        $this->assertSame(50, SearchSettings::perPage());
        $this->assertSame(5, SearchSettings::perType());
        $this->assertSame(80, SearchSettings::snippetLength());
    }

    #[Test]
    public function it_flattens_channel_groups_preserving_order(): void
    {
        $flat = SiteSearch::flatten([
            'docs' => collect([
                ['title' => 'A', 'score' => 10],
                ['title' => 'B', 'score' => 5],
            ]),
            'pages' => collect([
                ['title' => 'C', 'score' => 8],
            ]),
        ]);

        $this->assertSame(['A', 'B', 'C'], $flat->pluck('title')->all());
        $this->assertSame(['docs', 'docs', 'pages'], $flat->pluck('channel')->all());
    }

    #[Test]
    public function it_paginates_flat_results_with_query_string(): void
    {
        VoodbuilderSettings::saveData([
            'search_per_page' => 5,
        ]);

        $flat = collect(range(1, 12))->map(fn (int $n): array => [
            'title' => "Item {$n}",
            'channel' => 'docs',
            'url' => "/docs/{$n}",
        ]);

        $page1 = SiteSearch::paginate($flat, 1, 'blocks', null);
        $page2 = SiteSearch::paginate($flat, 2, 'blocks', 'docs');

        $this->assertSame(12, $page1->total());
        $this->assertCount(5, $page1->items());
        $this->assertSame('Item 1', $page1->items()[0]['title']);
        $this->assertStringContainsString('q=blocks', (string) $page1->url(2));
        $this->assertStringContainsString('type=docs', (string) $page2->url(1));
        $this->assertCount(5, $page2->items());
        $this->assertSame('Item 6', $page2->items()[0]['title']);
    }

    #[Test]
    public function it_clamps_out_of_range_page_to_last_page(): void
    {
        VoodbuilderSettings::saveData([
            'search_per_page' => 5,
        ]);

        $flat = collect(range(1, 3))->map(fn (int $n): array => [
            'title' => "Item {$n}",
            'channel' => 'docs',
            'url' => "/docs/{$n}",
        ]);

        $page = SiteSearch::paginate($flat, 9, 'modular', null);

        $this->assertSame(1, $page->currentPage());
        $this->assertCount(3, $page->items());
        $this->assertSame('Item 1', $page->items()[0]['title']);
    }

    #[Test]
    public function it_returns_empty_suggestions_for_short_or_unknown_terms(): void
    {
        $this->assertSame([], SiteSearch::suggest('m')['items']);
        $this->assertSame(0, SiteSearch::suggest('zz-no-such-term-ever')['total']);
    }
}
