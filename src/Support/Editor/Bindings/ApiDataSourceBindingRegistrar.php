<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Voodflow\Voodbuilder\Models\ApiDataSource;
use Voodflow\Voodbuilder\Support\DataSources\ApiDataSourceManager;
use Voodflow\Voodbuilder\Support\Editor\DynamicDataCollectionsBridge;

/**
 * Registers `{slug}.remote` / `{slug}.item` bindings and `{slug}.list` repeat sources.
 */
final class ApiDataSourceBindingRegistrar
{
    public function __construct(
        private readonly BindingRegistry $bindings,
        private readonly ApiDataSourceRegistry $registry,
    ) {}

    public function register(ApiDataSource $source): void
    {
        $this->unregister($source);

        if (! $source->enabled) {
            return;
        }

        $this->registry->register($source);
        $this->bindings->register(new ApiDataSourceRemoteBindingSource($source));

        // `.item` must stay registered for published List repeats even if authoring is gated.
        if (DynamicDataCollectionsBridge::renderingEnabled()) {
            $this->bindings->register(new ApiDataSourceItemBindingSource($source));
            $this->registerRepeatList($source);
        }
    }

    public function unregister(ApiDataSource $source): void
    {
        $this->bindings->forget($source->slug.'.remote');
        $this->bindings->forget($source->slug.'.item');
        $this->forgetRepeatList($source);
        $this->registry->forget($source);
    }

    public function refreshFromDatabase(): void
    {
        foreach ($this->registry->all() as $source) {
            $this->unregister($source);
        }

        try {
            ApiDataSource::query()
                ->where('enabled', true)
                ->each(fn (ApiDataSource $source) => $this->register($source));
        } catch (\Throwable) {
            // Table may not exist before migrate.
        }
    }

    private function registerRepeatList(ApiDataSource $source): void
    {
        if (! class_exists(RepeatListRegistry::class) || ! app()->bound(RepeatListRegistry::class)) {
            return;
        }

        $lists = app(RepeatListRegistry::class);
        $listId = $source->slug.'.list';

        $lists->register(
            $listId,
            $source->name.' · '.__('voodbuilder::api_data_sources.bindings.repeat_source'),
            function (int $limit, int $offset, string $sort, string $direction) use ($source): array {
                $take = max(1, min($limit + $offset, 50));
                $rows = app(ApiDataSourceManager::class)->resolve($source, ['limit' => $take]);
                $rows = $this->sortMappedRows($rows, $sort, $direction);
                $slice = array_slice($rows, max(0, $offset), max(1, min($limit, 24)));

                $out = [];
                foreach ($slice as $i => $row) {
                    $out[] = ApiDataSourceRow::fromMappedRow($source->slug, $row, $i);
                }

                return $out;
            },
            [
                ['id' => 'label', 'label' => __('voodbuilder::api_data_sources.bindings.fields.label')],
                ['id' => 'value', 'label' => __('voodbuilder::api_data_sources.bindings.fields.value')],
            ],
            'label',
            'asc',
        );
    }

    private function forgetRepeatList(ApiDataSource $source): void
    {
        if (! class_exists(RepeatListRegistry::class) || ! app()->bound(RepeatListRegistry::class)) {
            return;
        }

        app(RepeatListRegistry::class)->forget($source->slug.'.list');
    }

    /**
     * @param  list<array{value: mixed, label: string, meta: array<string, mixed>}>  $rows
     * @return list<array{value: mixed, label: string, meta: array<string, mixed>}>
     */
    private function sortMappedRows(array $rows, string $sort, string $direction): array
    {
        $dir = $direction === 'desc' ? -1 : 1;
        $key = $sort === 'value' ? 'value' : 'label';

        usort($rows, static function (array $a, array $b) use ($key, $dir): int {
            $left = (string) ($a[$key] ?? '');
            $right = (string) ($b[$key] ?? '');

            return $dir * strcasecmp($left, $right);
        });

        return $rows;
    }
}
