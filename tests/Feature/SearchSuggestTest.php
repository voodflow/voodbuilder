<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    #[Test]
    public function it_returns_json_suggestions(): void
    {
        $this->getJson(route('voodbuilder.search.suggest', ['q' => 'ab']))
            ->assertOk()
            ->assertJsonStructure([
                'query',
                'items',
                'total',
                'search_url',
            ])
            ->assertJsonPath('query', 'ab');
    }

    #[Test]
    public function it_returns_empty_items_for_short_queries(): void
    {
        $this->getJson(route('voodbuilder.search.suggest', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('items', [])
            ->assertJsonPath('total', 0);
    }
}
