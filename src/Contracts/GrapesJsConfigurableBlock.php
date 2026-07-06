<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

/**
 * Optional extension for server-rendered GrapesJS blocks with ad-hoc editor settings.
 *
 * Blocks implementing this contract expose a normalized config schema used by
 * the JS block-settings registry (see block-settings-registry.js).
 */
interface GrapesJsConfigurableBlock extends GrapesJsServerBlock
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalizeConfig(array $config): array;
}
