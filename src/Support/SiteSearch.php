<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;

/**
 * Site Search.
 */
final class SiteSearch
{
    /** Preferred channel order on the results page. */
    private const CHANNEL_ORDER = [
        'docs',
        'tutorials',
        'pages',
        'blog',
        'news',
        'events',
        'exhibitors',
        'partners',
        'sponsors',
    ];

    /**
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public static function search(string $term, ?string $type = null, ?int $perType = null): array
    {
        $term = trim($term);
        $results = [];
        $limit = $perType ?? SearchSettings::perType();
        $snippetLength = SearchSettings::snippetLength();

        foreach (self::indexableChannels() as $id => $channel) {
            if ($type !== null && $type !== $id) {
                continue;
            }

            if ($term === '') {
                continue;
            }

            $items = $channel->search($term, $limit)
                ->map(fn (array $item): array => self::enrichItem($item, $term, $snippetLength, $id))
                ->sortByDesc(fn (array $item): int => (int) ($item['score'] ?? 0))
                ->values();

            if ($items->isNotEmpty()) {
                $results[$id] = $items;
            }
        }

        return self::orderGroups($results);
    }

    /**
     * Top matches for the header search palette (global score ranking).
     *
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function suggest(string $term, ?int $limit = null): array
    {
        $term = trim($term);
        $limit = $limit ?? SearchSettings::suggestLimit();

        if ($term === '' || mb_strlen($term) < 2) {
            return ['items' => [], 'total' => 0];
        }

        $perType = min(SearchSettings::perType(), max($limit, 8));
        $grouped = self::search($term, null, $perType);
        $total = self::totalCount($grouped);
        $labels = self::typeLabels();

        $items = self::flatten($grouped)
            ->sortByDesc(fn (array $item): int => (int) ($item['score'] ?? 0))
            ->take($limit)
            ->map(function (array $item) use ($labels): array {
                $channel = (string) ($item['channel'] ?? '');

                return [
                    'title' => (string) ($item['title'] ?? ''),
                    'title_html' => (string) ($item['title_html'] ?? e((string) ($item['title'] ?? ''))),
                    'meta' => filled($item['meta'] ?? null) ? (string) $item['meta'] : null,
                    'url' => (string) ($item['url'] ?? '#'),
                    'channel' => $channel,
                    'channel_label' => $labels[$channel] ?? $channel,
                    'excerpt_html' => isset($item['excerpt_html']) ? (string) $item['excerpt_html'] : null,
                    'score' => (int) ($item['score'] ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, Collection<int, array<string, mixed>>>  $results
     */
    public static function totalCount(array $results): int
    {
        return (int) collect($results)->sum(fn (Collection $items): int => $items->count());
    }

    /**
     * @param  array<string, Collection<int, array<string, mixed>>>  $results
     * @return Collection<int, array<string, mixed>>
     */
    public static function flatten(array $results): Collection
    {
        $flat = collect();

        foreach ($results as $channelId => $items) {
            foreach ($items as $item) {
                $flat->push([
                    ...$item,
                    'channel' => $channelId,
                ]);
            }
        }

        return $flat->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $flat
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public static function paginate(
        Collection $flat,
        int $page,
        string $query,
        ?string $type,
    ): LengthAwarePaginator {
        $perPage = SearchSettings::perPage();
        $total = $flat->count();
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page = max(1, min($page, $lastPage));
        $items = $flat->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => VoodbuilderUrls::search(),
                'pageName' => 'page',
            ],
        );

        $paginator->appends(array_filter([
            'q' => $query,
            'type' => $type,
        ], fn ($value) => $value !== null && $value !== ''));

        return $paginator;
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
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected static function enrichItem(array $item, string $term, int $snippetLength, string $channelId): array
    {
        $title = (string) ($item['title'] ?? '');
        $meta = isset($item['meta']) ? (string) $item['meta'] : null;
        $body = isset($item['body']) ? (string) $item['body'] : null;
        $existingExcerpt = isset($item['excerpt']) ? (string) $item['excerpt'] : null;

        if (! isset($item['excerpt_html']) || ! filled($item['excerpt_html'])) {
            $presented = SearchExcerpt::present(
                $term,
                array_values(array_filter([
                    $item['preferred_excerpt'] ?? null,
                    $existingExcerpt,
                ], fn ($v) => filled($v))),
                $body ?? $existingExcerpt,
                $snippetLength,
            );
            $item['excerpt'] = $presented['excerpt'];
            $item['excerpt_html'] = $presented['excerpt_html'];
        } elseif (! isset($item['excerpt']) || ! filled($item['excerpt'])) {
            $item['excerpt'] = strip_tags((string) $item['excerpt_html']);
        }

        $scoreBody = trim(implode(' ', array_values(array_filter([
            $body,
            isset($item['preferred_excerpt']) ? (string) $item['preferred_excerpt'] : null,
            $existingExcerpt,
            isset($item['excerpt']) ? (string) $item['excerpt'] : null,
        ], fn ($value): bool => filled($value)))));

        $item['score'] = SearchExcerpt::score(
            $term,
            $title,
            $meta,
            $scoreBody !== '' ? $scoreBody : null,
        );

        $item['channel'] = $channelId;
        $item['title_html'] = SearchExcerpt::highlight($title, $term);

        unset($item['body'], $item['preferred_excerpt']);

        return $item;
    }

    /**
     * @param  array<string, Collection<int, array<string, mixed>>>  $results
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    protected static function orderGroups(array $results): array
    {
        $ordered = [];

        foreach (self::CHANNEL_ORDER as $id) {
            if (isset($results[$id])) {
                $ordered[$id] = $results[$id];
                unset($results[$id]);
            }
        }

        ksort($results);

        foreach ($results as $id => $items) {
            $ordered[$id] = $items;
        }

        return $ordered;
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

        $ordered = [];

        foreach (self::CHANNEL_ORDER as $id) {
            if (isset($channels[$id])) {
                $ordered[$id] = $channels[$id];
                unset($channels[$id]);
            }
        }

        ksort($channels);

        return $ordered + $channels;
    }
}
