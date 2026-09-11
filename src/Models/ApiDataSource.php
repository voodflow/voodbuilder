<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Enums\ApiDataSourceDriver;
use Voodflow\Voodbuilder\Support\DataSources\ApiDataSourceManager;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ApiDataSourceBindingRegistrar;

/**
 * Reusable remote / static data catalog for Editor field bindings.
 *
 * Pages bind by slug (e.g. `{slug}.remote.title`); the browser never calls the API.
 */
class ApiDataSource extends Model
{
    use HasUuids;

    protected $table = 'voodbuilder_api_data_sources';

    protected $fillable = [
        'name',
        'slug',
        'driver',
        'enabled',
        'config',
        'description',
        'cache_ttl_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'config' => 'array',
            'driver' => ApiDataSourceDriver::class,
            'cache_ttl_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $source): void {
            if (app()->bound(ApiDataSourceBindingRegistrar::class)) {
                app(ApiDataSourceBindingRegistrar::class)->register($source);
            }
        });

        static::deleted(function (self $source): void {
            if (app()->bound(ApiDataSourceBindingRegistrar::class)) {
                app(ApiDataSourceBindingRegistrar::class)->unregister($source);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function driverConfig(): array
    {
        return is_array($this->config) ? $this->config : [];
    }

    /**
     * Field ids exposed in the editor (value, label, meta keys, API scalars).
     *
     * @return list<string>
     */
    public function bindableFieldIds(): array
    {
        $ids = ['value', 'label'];
        $config = $this->driverConfig();
        $metaPaths = $config['meta_paths'] ?? $config['meta_columns'] ?? null;

        if (is_array($metaPaths)) {
            foreach (array_keys($metaPaths) as $key) {
                $key = (string) $key;
                if ($key === '' || in_array($key, $ids, true)) {
                    continue;
                }
                $ids[] = $key;
            }
        }

        $rows = $config['rows'] ?? null;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $meta = is_array($row['meta'] ?? null) ? $row['meta'] : [];
                foreach (array_keys($meta) as $key) {
                    $key = (string) $key;
                    if ($key === '' || in_array($key, $ids, true)) {
                        continue;
                    }
                    $ids[] = $key;
                }
            }
        }

        $discovered = $config['discovered_fields'] ?? null;
        if (is_array($discovered)) {
            foreach ($discovered as $key) {
                $key = (string) $key;
                if ($key === '' || in_array($key, $ids, true)) {
                    continue;
                }
                $ids[] = $key;
            }
        }

        // HTTP: sample one row so List item shows real API fields (firstName, email, …).
        if (
            $this->driver === ApiDataSourceDriver::Http
            && ($config['expose_all_fields'] ?? true)
            && count($ids) <= 2
            && app()->bound(ApiDataSourceManager::class)
        ) {
            try {
                $sample = app(ApiDataSourceManager::class)->resolvePrimary($this, ['limit' => 1]);
                $meta = is_array($sample['meta'] ?? null) ? $sample['meta'] : [];
                foreach (array_keys($meta) as $key) {
                    $key = (string) $key;
                    if ($key === '' || in_array($key, $ids, true)) {
                        continue;
                    }
                    $ids[] = $key;
                }
            } catch (\Throwable) {
                // Keep value/label only when the remote sample fails.
            }
        }

        return $ids;
    }
}
