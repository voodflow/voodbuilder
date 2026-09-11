<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources;

use Illuminate\Support\Facades\Cache;
use Voodflow\Voodbuilder\Enums\ApiDataSourceDriver;
use Voodflow\Voodbuilder\Models\ApiDataSource;
use Voodflow\Voodbuilder\Support\DataSources\Contracts\ApiDataSourceDriverInterface;
use Voodflow\Voodbuilder\Support\DataSources\Drivers\CallbackApiDataSourceDriver;
use Voodflow\Voodbuilder\Support\DataSources\Drivers\EloquentApiDataSourceDriver;
use Voodflow\Voodbuilder\Support\DataSources\Drivers\HttpApiDataSourceDriver;
use Voodflow\Voodbuilder\Support\DataSources\Drivers\StaticApiDataSourceDriver;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ApiDataSourceRow;

/**
 * Resolve page API data sources by slug / model — single entry point for bindings.
 */
final class ApiDataSourceManager
{
    /** @var array<string, ApiDataSourceDriverInterface> */
    private array $drivers = [];

    private readonly CallbackApiDataSourceDriver $callbackDriver;

    public function __construct()
    {
        $this->callbackDriver = new CallbackApiDataSourceDriver(
            is_array(config('voodbuilder.api_data_sources.callbacks'))
                ? config('voodbuilder.api_data_sources.callbacks')
                : [],
        );

        $this->drivers[ApiDataSourceDriver::Http->value] = new HttpApiDataSourceDriver;
        $this->drivers[ApiDataSourceDriver::Static->value] = new StaticApiDataSourceDriver;
        $this->drivers[ApiDataSourceDriver::Eloquent->value] = new EloquentApiDataSourceDriver;
        $this->drivers[ApiDataSourceDriver::Callback->value] = $this->callbackDriver;
    }

    public function registerDriver(string $key, ApiDataSourceDriverInterface $driver): void
    {
        $this->drivers[$key] = $driver;
    }

    public function registerCallback(string $key, callable|string $callback): void
    {
        $this->callbackDriver->registerCallback($key, $callback);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<array{value: mixed, label: string, meta: array<string, mixed>}>
     */
    public function resolve(ApiDataSource $source, array $params = []): array
    {
        if (! $source->enabled) {
            return [];
        }

        $driverKey = $source->driver instanceof ApiDataSourceDriver
            ? $source->driver->value
            : (string) $source->driver;

        $driver = $this->drivers[$driverKey] ?? null;
        if ($driver === null) {
            return [];
        }

        $ttl = max(0, (int) $source->cache_ttl_seconds);
        $cacheable = $ttl > 0
            && ! isset($params['query'])
            && ! isset($params['value']);

        if (! $cacheable) {
            return $driver->resolve($source->driverConfig(), $params);
        }

        $cacheKey = 'voodbuilder.api_data_source.'.$source->getKey().'.'.md5(json_encode($params) ?: '');

        return Cache::remember($cacheKey, $ttl, fn (): array => $driver->resolve($source->driverConfig(), $params));
    }

    /**
     * First row (or single object) for page bindings.
     *
     * @param  array<string, mixed>  $params
     * @return array{value: mixed, label: string, meta: array<string, mixed>}|null
     */
    public function resolvePrimary(ApiDataSource $source, array $params = []): ?array
    {
        $rows = $this->resolve($source, array_merge($params, [
            'limit' => 1,
        ]));

        return $rows[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<ApiDataSourceRow>
     */
    public function resolveRows(ApiDataSource $source, array $params = []): array
    {
        $mapped = $this->resolve($source, $params);
        $out = [];

        foreach ($mapped as $i => $row) {
            $out[] = ApiDataSourceRow::fromMappedRow($source->slug, $row, $i);
        }

        return $out;
    }

    public function findBySlug(string $slug): ?ApiDataSource
    {
        if ($slug === '') {
            return null;
        }

        try {
            return ApiDataSource::query()
                ->where('slug', $slug)
                ->where('enabled', true)
                ->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
