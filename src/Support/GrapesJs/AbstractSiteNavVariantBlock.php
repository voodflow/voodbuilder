<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsConfigurableBlock;
use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;

abstract class AbstractSiteNavVariantBlock implements GrapesJsConfigurableBlock
{
    abstract public static function variant(): string;

    public static function getId(): string
    {
        return 'site_nav_'.static::variant();
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::pro.grapesjs.blocks.site_nav_'.static::variant());
    }

    public static function defaultConfig(): array
    {
        return SiteNavConfig::defaults();
    }

    public static function normalizeConfig(array $config): array
    {
        return SiteNavConfig::normalize($config);
    }

    public static function toHtml(array $config, array $context): string
    {
        return self::renderShell($config, false);
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return self::renderShell($config, true);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function renderShell(array $config, bool $preview): string
    {
        $merged = SiteNavConfig::normalize(array_merge(static::defaultConfig(), $config));

        return view('voodbuilder::grapesjs.blocks.site-nav', [
            'config' => $merged,
            'preview' => $preview,
            'canvasPreview' => $preview,
        ])->render();
    }
}
