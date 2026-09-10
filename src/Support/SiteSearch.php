<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;

/**
 * Site Search.
 */
final class SiteSearch
{
    /**
     * @return array<string, Collection<int, array{title: string, url: string, excerpt?: string|null, meta?: string|null}>>
     */
    public static function search(string $term, ?string $type = null): array
    {
        $term = trim($term);
        $results = [];
        $limit = self::perType();

        foreach (self::indexableChannels() as $id => $channel) {
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
     * @param  array<string, Collection<int, array{title: string, url: string, excerpt?: string|null, meta?: string|null}>>  $results
     */
    public static function totalCount(array $results): int
    {
        return (int) collect($results)->sum(fn (Collection $items): int => $items->count());
    }

    /** @return list<string> */
    public static function availableTypes(): array
    {
        return array_keys(self::indexableChannels());
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        $labels = [];

        foreach (self::indexableChannels() as $id => $channel) {
            $labels[$id] = $channel->label();
        }

        return $labels;
    }

    /**
     * @return array<string, PublicContentChannel>
     */
    protected static function indexableChannels(): array
    {
        $channels = [];

        foreach (app(ContentChannelRegistry::class)->all() as $id => $channel) {
            if (method_exists($channel, 'appearsInSearchIndex') && $channel->appearsInSearchIndex() === false) {
                continue;
            }

            $channels[$id] = $channel;
        }

        return $channels;
    }

    protected static function perType(): int
    {
        return max(1, (int) config('voodbuilder.search.per_type', 20));
    }
}
