<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Editor media endpoints claimed by whichever media package the host installs.
 *
 * VoodBuilder never names a companion package: the defaults live in
 * `config('voodbuilder.editor.media')` and a provider can claim a capability at
 * runtime with {@see Voodbuilder::mediaEndpoint()}. Resolving a
 * capability is a `Route::has()` check, so a missing provider reports “not available”
 * and the editor degrades instead of fataling.
 */
final class MediaEndpointRegistry
{
    public const UPLOAD = 'upload';

    public const REPLACE = 'replace';

    public const META = 'meta';

    public const INDEX = 'index';

    public const GALLERIES = 'galleries';

    /** @var array<string, string> */
    private array $overrides = [];

    public function register(string $capability, string $routeName): self
    {
        $this->overrides[$capability] = $routeName;

        return $this;
    }

    public function forget(string $capability): self
    {
        unset($this->overrides[$capability]);

        return $this;
    }

    public function reset(): void
    {
        $this->overrides = [];
    }

    public function routeName(string $capability): ?string
    {
        $name = $this->overrides[$capability] ?? config("voodbuilder.editor.media.{$capability}");

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function available(string $capability): bool
    {
        $name = $this->routeName($capability);

        return $name !== null && Route::has($name);
    }
}
