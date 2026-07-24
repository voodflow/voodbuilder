<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Illuminate\Database\Eloquent\Model;

/**
 * Package-registered list queries for GrapesJS List repeat (outside Model Integrations).
 */
final class RepeatListRegistry
{
    /**
     * @var array<string, array{
     *     id: string,
     *     label: string,
     *     sortFields: list<array{id: string, label: string}>,
     *     defaultSort: string,
     *     defaultDirection: string,
     *     resolver: callable(int, int, string, string): list<Model>
     * }>
     */
    private array $lists = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /**
     * @param  callable(int $limit, int $offset, string $sort, string $direction): list<Model>  $resolver
     * @param  list<array{id: string, label: string}>  $sortFields
     */
    public function register(
        string $id,
        string $label,
        callable $resolver,
        array $sortFields = [],
        string $defaultSort = 'id',
        string $defaultDirection = 'desc',
    ): void {
        $this->lists[$id] = [
            'id' => $id,
            'label' => $label,
            'sortFields' => $sortFields === []
                ? [['id' => 'id', 'label' => 'ID']]
                : $sortFields,
            'defaultSort' => $defaultSort,
            'defaultDirection' => $defaultDirection === 'asc' ? 'asc' : 'desc',
            'resolver' => $resolver,
        ];
    }

    public function alias(string $aliasId, string $canonicalId): void
    {
        $this->aliases[$aliasId] = $canonicalId;
    }

    public function forget(string $id): void
    {
        unset($this->lists[$id]);

        foreach ($this->aliases as $alias => $canonical) {
            if ($alias === $id || $canonical === $id) {
                unset($this->aliases[$alias]);
            }
        }
    }

    public function has(string $id): bool
    {
        return $this->resolveId($id) !== null;
    }

    /**
     * @return list<array{id: string, label: string, sortFields: list<array{id: string, label: string}>, defaultSort: string, defaultDirection: string}>
     */
    public function catalog(): array
    {
        $items = [];

        foreach ($this->lists as $list) {
            $items[] = [
                'id' => $list['id'],
                'label' => $list['label'],
                'sortFields' => $list['sortFields'],
                'defaultSort' => $list['defaultSort'],
                'defaultDirection' => $list['defaultDirection'],
                'filters' => [],
            ];
        }

        return $items;
    }

    /**
     * @return list<Model>
     */
    public function resolve(
        string $repeatKey,
        int $limit = 6,
        ?string $sort = null,
        ?string $direction = null,
        int $offset = 0,
    ): array {
        $id = $this->resolveId($repeatKey);

        if ($id === null) {
            return [];
        }

        $list = $this->lists[$id];
        $column = $sort ?: $list['defaultSort'];
        $dir = ($direction ?: $list['defaultDirection']) === 'asc' ? 'asc' : 'desc';
        $skip = max(0, min($offset, 100));
        $take = max(1, min($limit, 24));

        $records = ($list['resolver'])($take, $skip, $column, $dir);

        return array_values(array_filter(
            $records,
            static fn (mixed $record): bool => $record instanceof Model,
        ));
    }

    private function resolveId(string $id): ?string
    {
        if (isset($this->lists[$id])) {
            return $id;
        }

        $canonical = $this->aliases[$id] ?? null;

        if ($canonical !== null && isset($this->lists[$canonical])) {
            return $canonical;
        }

        return null;
    }
}
