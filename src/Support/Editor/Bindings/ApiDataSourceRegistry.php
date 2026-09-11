<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Voodflow\Voodbuilder\Models\ApiDataSource;

/**
 * In-memory registry of enabled API data sources for binding registration.
 */
final class ApiDataSourceRegistry
{
    /** @var array<string, ApiDataSource> */
    private array $sources = [];

    public function register(ApiDataSource $source): void
    {
        $this->sources[$source->slug] = $source;
    }

    public function forget(ApiDataSource $source): void
    {
        unset($this->sources[$source->slug]);
    }

    /**
     * @return list<ApiDataSource>
     */
    public function all(): array
    {
        return array_values($this->sources);
    }
}
