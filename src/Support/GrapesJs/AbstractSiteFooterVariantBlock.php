<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;

abstract class AbstractSiteFooterVariantBlock implements GrapesJsServerBlock
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
        return [
            'columns' => static::defaultColumns(),
        ];
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
        $inner = view('voodbuilder::grapesjs.blocks.footers.'.static::variant(), [
            'config' => $config,
            'preview' => $preview,
        ])->render();

        $shell = GrapesJsFooterBlockShell::compose(static::getId(), $config, $inner);

        return GrapesJsSlotHydrator::hydrateHtml($shell, $preview);
    }
}
