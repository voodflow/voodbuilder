<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Closure;
use Illuminate\Support\Collection;
use Voodflow\Vpress\Contracts\PublicContentChannel;

final class ContentChannelRegistry
{
    /** @var array<string, PublicContentChannel> */
    private array $channels = [];

    public function bootFromConfig(): void
    {
        foreach (config('vpress.content_channels', []) as $id => $definition) {
            if (! is_string($id) || ! is_array($definition)) {
                continue;
            }

            $this->registerFromArray($id, $definition);
        }
    }

    /**
     * @param  array{
     *     label?: string,
     *     routes?: list<string>,
     *     sub_theme?: string|null,
     *     search?: Closure|string|null,
     * }  $definition
     */
    public function registerFromArray(string $id, array $definition): self
    {
        $this->channels[$id] = new ConfiguredContentChannel(
            id: $id,
            label: (string) ($definition['label'] ?? str($id)->headline()->toString()),
            routePatterns: array_values($definition['routes'] ?? []),
            subTheme: $definition['sub_theme'] ?? null,
            searchResolver: $this->resolveSearch($definition['search'] ?? null),
        );

        return $this;
    }

    public function register(PublicContentChannel $channel): self
    {
        $this->channels[$channel->id()] = $channel;

        return $this;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->channels);
    }

    public function get(string $id): ?PublicContentChannel
    {
        return $this->channels[$id] ?? null;
    }

    /** @return array<string, PublicContentChannel> */
    public function all(): array
    {
        return $this->channels;
    }

    public function matchesCurrentRequest(): ?PublicContentChannel
    {
        foreach ($this->channels as $channel) {
            foreach ($channel->routePatterns() as $pattern) {
                if (request()->routeIs($pattern)) {
                    return $channel;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, Collection<int, array{title: string, url: string, excerpt?: string|null}>>
     */
    public function search(string $term, int $limitPerChannel = 20): array
    {
        $results = [];

        foreach ($this->channels as $channel) {
            $items = $channel->search($term, $limitPerChannel);

            if ($items->isNotEmpty()) {
                $results[$channel->id()] = $items;
            }
        }

        return $results;
    }

    private function resolveSearch(mixed $search): ?Closure
    {
        if ($search instanceof Closure) {
            return $search;
        }

        if (! is_string($search) || $search === '') {
            return null;
        }

        return static function (string $term, int $limit) use ($search): Collection {
            if (! class_exists($search) || ! method_exists($search, 'vpressSearch')) {
                return collect();
            }

            return $search::vpressSearch($term, $limit);
        };
    }
}
