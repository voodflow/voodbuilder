<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Contracts\EditorConfigurableBlock;

/**
 * Rich content / landing block: Abstract Site Nav Variant Block.
 */
abstract class AbstractSiteNavVariantBlock implements EditorConfigurableBlock
{
    abstract public static function variant(): string;

    public static function getId(): string
    {
        return 'site_nav_'.static::variant();
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::pro.editor.blocks.site_nav_'.static::variant());
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
        // Normalize alone merges defaults; avoid pre-merging defaultConfig() so
        // explicit show_logo=false is not overridden by default breakpoint flags.
        $merged = SiteNavConfig::normalize($config);

        return view('voodbuilder::editor.blocks.site-nav', [
            'config' => $merged,
            'preview' => $preview,
            'canvasPreview' => $preview,
        ])->render();
    }
}
