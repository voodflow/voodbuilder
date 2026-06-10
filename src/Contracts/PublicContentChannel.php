<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Contracts;

use Illuminate\Support\Collection;

/**
 * Hook for blog, news, or other content systems that live outside Site Pages.
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
     * @return Collection<int, array{title: string, url: string, excerpt?: string|null}>
     */
    public function search(string $term, int $limit = 20): Collection;
}
