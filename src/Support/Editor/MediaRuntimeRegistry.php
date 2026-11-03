<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Boot hooks for the media runtime behind the editor's Asset Manager.
 *
 * A media package registers how to bring its routes up here; VoodBuilder only asks
 * “is there an upload endpoint yet?”. The configured fallback in
 * `config('voodbuilder.integrations.media_runtime_class')` keeps existing installs
 * working without VoodBuilder naming any package in its source.
 */
final class MediaRuntimeRegistry
{
    /** @var list<callable(): void> */
    private array $booters = [];

    private bool $booted = false;

    public function register(callable $boot): self
    {
        $this->booters[] = $boot;

        return $this;
    }

    public function reset(): void
    {
        $this->booters = [];
        $this->booted = false;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        foreach ($this->booters as $boot) {
            $boot();
        }

        $this->bootConfiguredRuntime();
    }

    private function bootConfiguredRuntime(): void
    {
        $class = config('voodbuilder.integrations.media_runtime_class');

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return;
        }

        $reset = (string) config('voodbuilder.integrations.media_runtime_reset_method', 'reset');
        $activate = (string) config('voodbuilder.integrations.media_runtime_activation_method', 'activate');

        // The static “active” flag outlives an application refresh (tests), so reset
        // before activating: an activate() that early-returns on that flag is a no-op.
        if (method_exists($class, $reset)) {
            $class::$reset();
        }

        if (method_exists($class, $activate)) {
            $class::$activate();
        }
    }
}
