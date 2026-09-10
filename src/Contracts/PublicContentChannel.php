<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Illuminate\Support\Collection;

/**
 * Hook for blog, news, docs, or other content systems that live outside Site Pages.
 *
 * Channels drive chrome / Theme Map areas and optionally the public site search
 * (header palette + /search). See docs/manual/developer/php-sdk/site-search.md.
 */
interface PublicContentChannel
{
    public function id(): string;

    public function label(): string;

    /**
     * Route name patterns used to keep navigation items highlighted (e.g. blog.*).
     *
     * @return list<string>
     */
    public function routePatterns(): array;

    /**
     * Optional sub-theme applied when the current route matches this channel.
     */
    public function subTheme(): ?string;

    /**
     * Public search hits for this channel.
     *
     * Return at least `title` + `url`. Prefer also `meta`, plain `body` /
     * `preferred_excerpt`, and optional precomputed `excerpt` / `excerpt_html`
     * / `score`. Core enriches snippets and ranking via SearchExcerpt.
     *
     * Honour `$limit`, current locale, and published/public visibility.
     *
     * Optional opt-out from the search index (chrome-only channels):
     * implement `appearsInSearchIndex(): bool` returning false.
     *
     * @return Collection<int, array{
     *     title: string,
     *     url: string,
     *     meta?: string|null,
     *     excerpt?: string|null,
     *     excerpt_html?: string|null,
     *     body?: string|null,
     *     preferred_excerpt?: string|null,
     *     score?: int
     * }>
     */
    public function search(string $term, int $limit = 20): Collection;
}
