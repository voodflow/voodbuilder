<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;
use Voodflow\Voodbuilder\Contracts\VoodBuilderModule;

final class ModuleRegistry
{
    /** @var array<string, VoodBuilderModule> */
    private array $modules = [];

    /** @var array<string, bool> */
    private array $enabled = [];

    private bool $booted = false;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function register(VoodBuilderModule $module, bool $enabled = true): void
    {
        $id = $module->id();

        if ($id === '') {
            throw new InvalidArgumentException('Module id must not be empty.');
        }

        if (isset($this->modules[$id])) {
            throw new InvalidArgumentException("Module [{$id}] is already registered.");
        }

        $this->modules[$id] = $module;
        $this->enabled[$id] = $enabled;

        // Companion Filament plugins may register after the initial ModuleRegistry::boot().
        if ($this->booted && $enabled) {
            $this->assertDependencies($module);
            $module->register($this->contextFor($module));
            $module->boot($this->contextFor($module));
            $this->app['router']->getRoutes()->refreshNameLookups();
            $this->app['router']->getRoutes()->refreshActionLookups();
        }
    }

    /**
     * @return Collection<int, VoodBuilderModule>
     */
    public function all(): Collection
    {
        return collect(array_values($this->modules));
    }

    /**
     * @return Collection<int, VoodBuilderModule>
     */
    public function enabled(): Collection
    {
        return $this->all()->filter(
            fn (VoodBuilderModule $module): bool => $this->isEnabled($module->id()),
        )->values();
    }

    public function get(string $id): ?VoodBuilderModule
    {
        return $this->modules[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->modules[$id]);
    }

    public function isEnabled(string $id): bool
    {
        return ($this->enabled[$id] ?? false) === true;
    }

    public function enable(string $id): void
    {
        $this->assertKnown($id);
        $this->enabled[$id] = true;
    }

    public function disable(string $id): void
    {
        $this->assertKnown($id);
        $this->enabled[$id] = false;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $enabled = $this->enabled();

        foreach ($enabled as $module) {
            $this->assertDependencies($module);
        }

        foreach ($enabled as $module) {
            $module->register($this->contextFor($module));
        }

        foreach ($enabled as $module) {
            $module->boot($this->contextFor($module));
        }

        $this->booted = true;
    }

    public function booted(): bool
    {
        return $this->booted;
    }

    /**
     * @return Collection<int, object>
     */
    public function contributors(string $interface): Collection
    {
        return $this->enabled()->filter(
            fn (VoodBuilderModule $module): bool => $module instanceof $interface,
        )->values();
    }

    private function contextFor(VoodBuilderModule $module): ModuleContext
    {
        return new ModuleContext(
            app: $this->app,
            config: (array) config('voodbuilder.modules.'.$module->id(), []),
            enabled: $this->isEnabled($module->id()),
        );
    }

    private function assertKnown(string $id): void
    {
        if (! $this->has($id)) {
            throw new InvalidArgumentException("Unknown module [{$id}].");
        }
    }

    private function assertDependencies(VoodBuilderModule $module): void
    {
        foreach ($module->dependencies() as $dependency) {
            if (! $this->has($dependency) || ! $this->isEnabled($dependency)) {
                throw new RuntimeException(
                    "Module [{$module->id()}] requires enabled dependency [{$dependency}].",
                );
            }
        }
    }
}
