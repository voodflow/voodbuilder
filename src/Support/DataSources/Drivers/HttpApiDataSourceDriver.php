<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources\Drivers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Support\DataSources\Contracts\ApiDataSourceDriverInterface;
use Voodflow\Voodbuilder\Support\DataSources\Contracts\SuggestsMetaKeys;
use Voodflow\Voodbuilder\Support\DataSources\HttpUrlGuard;
use Voodflow\Voodbuilder\Support\DataSources\TokenResolver;

/**
 * HTTP(S) page data source — maps JSON into {value, label, meta} rows.
 *
 * Config: url, method, headers, query, body, response_path, value_path,
 * label_path, meta_paths{}, query_param, value_param, local_filter, min_query_length.
 */
final class HttpApiDataSourceDriver implements ApiDataSourceDriverInterface, SuggestsMetaKeys
{
    public function suggestMetaKeys(array $config = []): array
    {
        $metaPaths = $config['meta_paths'] ?? null;
        if (! is_array($metaPaths)) {
            return [];
        }

        return array_values(array_filter(
            array_map('strval', array_keys($metaPaths)),
            static fn (string $key): bool => $key !== '',
        ));
    }

    public function resolve(array $config, array $params = []): array
    {
        $tokenContext = $this->tokenContext($params);
        $url = trim((string) TokenResolver::resolve((string) ($config['url'] ?? ''), $tokenContext));
        if ($url === '') {
            return [];
        }

        if (! (bool) config('voodbuilder.api_data_sources.http.allow_relative_urls', false)) {
            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                return [];
            }
        }

        if ((bool) config('voodbuilder.api_data_sources.http.block_ssrf', true)
            && ! HttpUrlGuard::isSafeHttpUrl($url)
        ) {
            return [];
        }

        $queryTerm = isset($params['query']) ? trim((string) $params['query']) : '';
        $minQuery = (int) ($config['min_query_length'] ?? 0);
        $hasExactValue = isset($params['value']) && ($config['value_param'] ?? null);
        if ($minQuery > 0 && $queryTerm !== '' && mb_strlen($queryTerm) < $minQuery && ! $hasExactValue) {
            return [];
        }

        $method = strtoupper((string) ($config['method'] ?? 'GET'));
        $headers = is_array($config['headers'] ?? null) ? $config['headers'] : [];
        $resolvedHeaders = [];
        foreach ($headers as $headerKey => $headerValue) {
            $resolvedHeaders[(string) $headerKey] = (string) TokenResolver::resolve((string) $headerValue, $tokenContext);
        }
        $headers = $resolvedHeaders;

        $query = is_array($config['query'] ?? null) ? $config['query'] : [];
        $body = is_array($config['body'] ?? null) ? $config['body'] : [];
        foreach ($query as $queryKey => $queryValue) {
            if (is_string($queryValue)) {
                $query[$queryKey] = TokenResolver::resolve($queryValue, $tokenContext);
            }
        }
        foreach ($body as $bodyKey => $bodyValue) {
            if (is_string($bodyValue)) {
                $body[$bodyKey] = TokenResolver::resolve($bodyValue, $tokenContext);
            }
        }

        $searchParam = (string) ($config['query_param'] ?? 'q');
        if ($queryTerm !== '' && $searchParam !== '') {
            $query[$searchParam] = $queryTerm;
        }
        if ($hasExactValue) {
            $query[(string) $config['value_param']] = $params['value'];
        }

        $timeout = (int) config('voodbuilder.api_data_sources.http.timeout', 10);
        $connectTimeout = (int) config('voodbuilder.api_data_sources.http.connect_timeout', 5);

        $pending = Http::timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->withoutRedirecting()
            ->withHeaders($headers)
            ->acceptJson();

        $response = match ($method) {
            'POST' => $pending->post($url, array_merge($body, $query)),
            'PUT' => $pending->put($url, array_merge($body, $query)),
            default => $pending->get($url, $query),
        };

        if (! $response->successful()) {
            return [];
        }

        $json = $response->json();
        $rows = $this->extractRows($json, (string) ($config['response_path'] ?? ''));

        return $this->mapRows($rows, $config, $params);
    }

    /**
     * @return list<mixed>
     */
    private function extractRows(mixed $json, string $path): array
    {
        $rows = null;

        if ($path !== '' && is_array($json)) {
            $rows = Arr::get($json, $path);
        } elseif (is_array($json)) {
            $rows = $json;
        }

        if (is_array($rows) && $rows !== [] && ! array_is_list($rows) && $path === '') {
            // Top-level object with a list under a common key (DummyJSON: { users: [...] }).
            $rows = null;
        }

        if (! is_array($rows) || $rows === []) {
            foreach (['users', 'data', 'results', 'items', 'products', 'records'] as $candidate) {
                if (! is_array($json)) {
                    break;
                }
                $candidateRows = Arr::get($json, $candidate);
                if (is_array($candidateRows) && ($candidateRows === [] || array_is_list($candidateRows))) {
                    $rows = $candidateRows;
                    break;
                }
            }
        }

        if (! is_array($rows)) {
            return [];
        }

        if ($rows !== [] && ! array_is_list($rows)) {
            return [$rows];
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $rows
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $params
     * @return list<array{value: mixed, label: string, meta: array<string, mixed>}>
     */
    private function mapRows(array $rows, array $config, array $params): array
    {
        $valuePath = (string) ($config['value_path'] ?? 'id');
        $labelPath = (string) ($config['label_path'] ?? 'name');
        $metaPaths = is_array($config['meta_paths'] ?? null) ? $config['meta_paths'] : [];
        $exposeAll = (bool) ($config['expose_all_fields'] ?? true);
        $limit = isset($params['limit'])
            ? (int) $params['limit']
            : (int) config('voodbuilder.api_data_sources.max_limit', 25);
        $limit = max(1, min(50, $limit));
        $localFilter = (bool) ($config['local_filter'] ?? true);
        $queryTerm = isset($params['query']) ? trim((string) $params['query']) : '';
        $needle = $localFilter && $queryTerm !== '' ? mb_strtolower($queryTerm) : '';
        $exactValue = isset($params['value']) ? trim((string) $params['value']) : '';

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $meta = [];
            if ($exposeAll) {
                $meta = $this->scalarFields($row);
            }
            foreach ($metaPaths as $key => $metaPath) {
                $meta[(string) $key] = Arr::get($row, (string) $metaPath);
            }

            $value = Arr::get($row, $valuePath);
            $label = $this->resolveLabel($row, $labelPath, $value);

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

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function scalarFields(array $row, string $prefix = ''): array
    {
        $fields = [];

        foreach ($row as $key => $value) {
            $key = (string) $key;
            if ($key === '') {
                continue;
            }

            $path = $prefix === '' ? $key : $prefix.'.'.$key;

            if (is_scalar($value) || $value === null) {
                $fields[$path] = $value;

                continue;
            }

            // One nested level of objects (e.g. address.city) — keep lists out of bindings.
            if (is_array($value) && ! array_is_list($value) && $prefix === '') {
                foreach ($this->scalarFields($value, $path) as $nestedKey => $nestedValue) {
                    $fields[$nestedKey] = $nestedValue;
                }
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveLabel(array $row, string $labelPath, mixed $value): string
    {
        $label = Arr::get($row, $labelPath);
        if (is_scalar($label) && trim((string) $label) !== '') {
            return (string) $label;
        }

        $composed = trim(implode(' ', array_filter([
            is_scalar(Arr::get($row, 'firstName') ?? null) ? (string) Arr::get($row, 'firstName') : null,
            is_scalar(Arr::get($row, 'lastName') ?? null) ? (string) Arr::get($row, 'lastName') : null,
        ], static fn (?string $part): bool => $part !== null && $part !== '')));

        if ($composed !== '') {
            return $composed;
        }

        foreach (['name', 'title', 'username', 'email', 'label'] as $fallback) {
            $candidate = Arr::get($row, $fallback);
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return (string) $candidate;
            }
        }

        return (string) ($value ?? '');
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function tokenContext(array $params): array
    {
        $request = function_exists('request') ? request() : null;

        return array_merge([
            'params' => $params,
            'query' => $params['query'] ?? null,
            'value' => $params['value'] ?? null,
            'route' => $request?->route()?->parameters() ?? [],
            'request' => [
                'path' => $request?->path(),
                'url' => $request?->url(),
                'locale' => $request?->getLocale(),
            ],
        ], $params);
    }
}
