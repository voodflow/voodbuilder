<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Voodflow\Voodbuilder\Contracts\DynamicPageProvider;

/**
 * Registry of companion dynamic page providers.
 */
final class DynamicPageRegistry
{
    /** @var array<string, DynamicPageProvider> */
    private array $providers = [];

    public function register(DynamicPageProvider $provider): self
    {
        $this->providers[$provider->channelId()] = $provider;

        return $this;
    }

    public function get(string $channelId): ?DynamicPageProvider
    {
        return $this->providers[$channelId] ?? null;
    }

    /**
     * @return array<string, DynamicPageProvider>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * @return array<string, string>
     */
    public function channelOptions(): array
    {
        $options = [];

        foreach ($this->providers as $id => $provider) {
            $options[$id] = $provider->channelLabel();
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function routeOptions(string $channelId): array
    {
        return $this->get($channelId)?->claimableRoutes() ?? [];
    }
}
