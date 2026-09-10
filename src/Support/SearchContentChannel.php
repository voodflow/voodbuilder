<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;

/**
 * Surface channel for /search — chrome / Theme Map only (not a search filter).
 */
final class SearchContentChannel implements PublicContentChannel
{
    public function id(): string
    {
        return 'search';
    }

    public function label(): string
    {
        return __('voodbuilder::search.filters.search');
    }

    public function routePatterns(): array
    {
        return ['voodbuilder.search'];
    }

    public function subTheme(): ?string
    {
        return null;
    }

    public function search(string $term, int $limit = 20): Collection
    {
        return collect();
    }

    /**
     * Exclude from site-search type pills — this channel is the results page itself.
     */
    public function appearsInSearchIndex(): bool
    {
        return false;
    }
}
