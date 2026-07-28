<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

final class EntitlementManager
{
    public function __construct(
        private EntitlementProvider $provider,
    ) {}

    public function provider(): EntitlementProvider
    {
        return $this->provider;
    }

    public function useProvider(EntitlementProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function can(string $capability): bool
    {
        if ($capability === '') {
            return false;
        }

        return $this->provider->capabilities()->has($capability);
    }

    public function cannot(string $capability): bool
    {
        return ! $this->can($capability);
    }

    /**
     * @param  list<string>  $capabilities
     */
    public function canAny(array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if ($this->can($capability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $capabilities
     */
    public function canAll(array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if (! $this->can($capability)) {
                return false;
            }
        }

        return true;
    }

    public function capabilities(): CapabilitySet
    {
        return $this->provider->capabilities();
    }

    public function licenceStatus(): LicenceStatus
    {
        return $this->provider->licenceStatus();
    }

    public function edition(): string
    {
        return $this->licenceStatus()->edition;
    }
}
