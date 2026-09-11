<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources\Contracts;

/**
 * Resolves a page data source into uniform rows: {value, label, meta}.
 */
interface ApiDataSourceDriverInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $params
     * @return list<array{value: mixed, label: string, meta: array<string, mixed>}>
     */
    public function resolve(array $config, array $params = []): array;
}
