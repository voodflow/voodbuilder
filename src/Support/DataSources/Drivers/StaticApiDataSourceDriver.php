<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources\Drivers;

use Voodflow\Voodbuilder\Support\DataSources\Contracts\ApiDataSourceDriverInterface;
use Voodflow\Voodbuilder\Support\DataSources\Contracts\SuggestsMetaKeys;

/**
 * Static rows for demos / curated catalogs without an HTTP call.
 */
final class StaticApiDataSourceDriver implements ApiDataSourceDriverInterface, SuggestsMetaKeys
{
    public function suggestMetaKeys(array $config = []): array
    {
        $keys = [];
        $rows = $config['rows'] ?? null;
        if (! is_array($rows)) {
            return [];
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $meta = is_array($row['meta'] ?? null) ? $row['meta'] : [];
            foreach (array_keys($meta) as $key) {
                $keys[(string) $key] = true;
            }
        }

        return array_keys($keys);
    }

    public function resolve(array $config, array $params = []): array
    {
        $rows = $config['rows'] ?? null;
        if (! is_array($rows)) {
            return [];
        }

        $limit = isset($params['limit'])
            ? (int) $params['limit']
            : (int) config('voodbuilder.api_data_sources.max_limit', 25);
        $limit = max(1, min(50, $limit));
        $queryTerm = isset($params['query']) ? trim((string) $params['query']) : '';
        $needle = $queryTerm !== '' ? mb_strtolower($queryTerm) : '';
        $exactValue = isset($params['value']) ? trim((string) $params['value']) : '';

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $value = $row['value'] ?? null;
            $label = (string) ($row['label'] ?? $value ?? '');
            $meta = is_array($row['meta'] ?? null) ? $row['meta'] : [];

            $mapped = [
                'value' => $value,
                'label' => $label,
                'meta' => $meta,
            ];

            if ($exactValue !== '' && (string) $value !== $exactValue) {
                continue;
            }

            if ($needle !== '') {
                $haystack = mb_strtolower($label.' '.(string) $value.' '.json_encode($meta));
                if (! str_contains($haystack, $needle)) {
                    continue;
                }
            }

            $out[] = $mapped;
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }
}
