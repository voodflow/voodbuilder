<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules;

use Illuminate\Contracts\Foundation\Application;

/**
 * Shared container for module register/boot hooks.
 *
 * @deprecated-bridge remove-by 0.2.0 once all modules migrate off direct ServiceProvider wiring
 */
final class ModuleContext
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public readonly Application $app,
        public readonly array $config = [],
        public readonly bool $enabled = true,
    ) {}

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
}
