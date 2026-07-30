<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;

/**
 * Site Search.
 */
final class SiteSearch
{
    /**
     * @return array<string, Collection<int, array{title: string, url: string, excerpt?: string|null}>>
     */
    public static function search(string $term, ?string $type = null): array
    {
        $term = trim($term);
        $results = [];
        $limit = self::perType();

        foreach (app(ContentChannelRegistry::class)->all() as $id => $channel) {
            if ($type !== null && $type !== $id) {
                continue;
            }

            if ($term === '') {
                continue;
            }

            $items = $channel->search($term, $limit);

            if ($items->isNotEmpty()) {
                $results[$id] = $items;
            }
        }

        return $results;
    }

    /**
     * @param  array<string, Collection<int, array{title: string, url: string, excerpt?: string|null}>>  $results
     */
    public static function totalCount(array $results): int
    {
        return (int) collect($results)->sum(fn (Collection $items): int => $items->count());
    }

    /** @return list<string> */
    public static function availableTypes(): array
    {
        return array_keys(app(ContentChannelRegistry::class)->all());
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        $labels = [];

        foreach (app(ContentChannelRegistry::class)->all() as $id => $channel) {
            $labels[$id] = $channel->label();
        }

        return $labels;
    }

    protected static function perType(): int
    {
        return max(1, (int) config('voodbuilder.search.per_type', 20));
    }
}
