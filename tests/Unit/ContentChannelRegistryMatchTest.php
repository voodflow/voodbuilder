<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;

class ContentChannelRegistryMatchTest extends TestCase
{
    public function test_wildcard_prefix_patterns_like_vtuts_still_match(): void
    {
        Route::get('/tutorials', fn () => 'ok')->name('vtuts.index');

        $this->get('/tutorials');

        $registry = app(ContentChannelRegistry::class);
        $registry->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'tutorials';
            }

            public function label(): string
            {
                return 'Tutorials';
            }

            public function routePatterns(): array
            {
                return ['vtuts.*'];
            }

            public function subTheme(): ?string
            {
                return null;
            }

            public function search(string $term, int $limit = 20): Collection
            {
                return collect();
            }
        });

        $this->assertTrue(request()->routeIs('vtuts.*'));
        $this->assertSame('tutorials', $registry->matchesCurrentRequest()?->id());
    }

    public function test_prefers_more_specific_route_patterns(): void
    {
        Route::get('/tutorials/series', fn () => 'ok')->name('vtuts.series.index');

        $this->get('/tutorials/series');

        $registry = app(ContentChannelRegistry::class);
        $registry->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'tutorials';
            }

            public function label(): string
            {
                return 'Tutorials';
            }

            public function routePatterns(): array
            {
                return ['vtuts.*'];
            }

            public function subTheme(): ?string
            {
                return null;
            }

            public function search(string $term, int $limit = 20): Collection
            {
                return collect();
            }
        });
        $registry->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'series';
            }

            public function label(): string
            {
                return 'Series';
            }

            public function routePatterns(): array
            {
                return ['vtuts.series.*'];
            }

            public function subTheme(): ?string
            {
                return null;
            }

            public function search(string $term, int $limit = 20): Collection
            {
                return collect();
            }
        });

        $this->assertSame('series', $registry->matchesCurrentRequest()?->id());
    }
}
