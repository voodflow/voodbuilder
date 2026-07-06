<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsConfigurableBlock;

abstract class AbstractSiteFooterVariantBlock implements GrapesJsConfigurableBlock
{
    abstract public static function variant(): string;

    abstract public static function defaultColumns(): int;

    public static function getId(): string
    {
        return 'site_footer_'.static::variant();
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::pro.grapesjs.blocks.site_footer_'.static::variant());
    }

    public static function defaultConfig(): array
    {
        return SiteFooterConfig::normalize([
            'columns' => static::defaultColumns(),
        ]);
    }

    public static function normalizeConfig(array $config): array
    {
        return SiteFooterConfig::normalize($config);
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
        $merged = SiteFooterConfig::normalize(array_merge(static::defaultConfig(), $config));

        $inner = view('voodbuilder::grapesjs.blocks.footers.'.static::variant(), [
            'config' => $merged,
            'preview' => $preview,
        ])->render();

        $shell = GrapesJsFooterBlockShell::compose(static::getId(), $merged, $inner);

        return GrapesJsSlotHydrator::hydrateHtml($shell, $preview);
    }
}
