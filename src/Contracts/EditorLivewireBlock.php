<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

/**
 * Server block whose published output is a mounted Livewire component.
 *
 * The canvas always renders `toPreviewHtml()` — static, Blade-owned markup. The
 * editor never mounts Livewire, so save/load, anchoring and Tailwind compilation
 * keep working exactly as they do for any other server block; only the published
 * page mounts the component.
 *
 * Register with `Voodbuilder::editorServerBlock()`. The registry is shared with
 * plain server blocks; this interface is what switches the render behaviour.
 *
 * Soft dependency: the contract loads without `livewire/livewire`. When Livewire
 * is absent the published page falls back to `toPreviewHtml()`.
 */
interface EditorLivewireBlock extends EditorServerBlock
{
    /**
     * Livewire component name, as registered (e.g. `voodpricing::plan-table`).
     */
    public static function component(): string;

    /**
     * Config → public component props.
     *
     * Only the returned keys reach Livewire, which makes the mapping an allowlist
     * by construction: page config is author-controlled and must not be forwarded
     * wholesale.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function propsFromConfig(array $config): array;
}
