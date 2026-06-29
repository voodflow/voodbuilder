<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

/**
 * Server-rendered GrapesJS block for third-party packages (no Filament RichEditor required).
 *
 * Register in your ServiceProvider:
 *   Voodbuilder::grapesJsServerBlock('MyPackage', MyBlock::class);
 */
interface GrapesJsServerBlock
{
    public static function getId(): string;

    public static function getLabel(): string;

    /**
     * @return array<string, mixed>
     */
    public static function defaultConfig(): array;

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    public static function toHtml(array $config, array $context): string;

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    public static function toPreviewHtml(array $config, array $context): string;
}
