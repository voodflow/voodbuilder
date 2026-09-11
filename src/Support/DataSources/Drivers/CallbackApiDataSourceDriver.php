<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources\Drivers;

use Voodflow\Voodbuilder\Support\DataSources\Contracts\ApiDataSourceDriverInterface;
use Voodflow\Voodbuilder\Support\DataSources\Contracts\SuggestsMetaKeys;

/**
 * Invokes a named host callback registered on ApiDataSourceManager / config.
 *
 * Config: { "callback": "products-feed" }
 * Signature: fn(array $params, array $config): list<array{value,label,meta}>
 */
final class CallbackApiDataSourceDriver implements ApiDataSourceDriverInterface, SuggestsMetaKeys
{
    /**
     * @param  array<string, callable|class-string>  $callbacks
     */
    public function __construct(
        private array $callbacks = [],
    ) {}

    /**
     * @param  array<string, callable|class-string>  $callbacks
     */
    public function setCallbacks(array $callbacks): void
    {
        $this->callbacks = $callbacks;
    }

    public function registerCallback(string $key, callable|string $callback): void
    {
        $this->callbacks[$key] = $callback;
    }

    public function resolve(array $config, array $params = []): array
    {
        $callback = $this->resolveRegistered($config);
        if (! is_callable($callback)) {
            return [];
        }

        $result = $callback($params, $config);

        return is_array($result) ? array_values($result) : [];
    }

    public function suggestMetaKeys(array $config = []): array
    {
        $callback = $this->resolveRegistered($config);
        if (! $callback instanceof SuggestsMetaKeys) {
            return [];
        }

        return array_values(array_filter(
            array_map('strval', $callback->suggestMetaKeys($config)),
            static fn (string $key): bool => $key !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveRegistered(array $config): mixed
    {
        $key = (string) ($config['callback'] ?? '');
        if ($key === '' || ! isset($this->callbacks[$key])) {
            return null;
        }

        $callback = $this->callbacks[$key];
        if (is_string($callback) && class_exists($callback)) {
            $callback = function_exists('app') ? app($callback) : new $callback;
        }

        return $callback;
    }
}
