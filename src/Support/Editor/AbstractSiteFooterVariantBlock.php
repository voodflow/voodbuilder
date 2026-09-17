<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Contracts\EditorConfigurableBlock;

/**
 * Rich content / landing block: Abstract Site Footer Variant Block.
 */
abstract class AbstractSiteFooterVariantBlock implements EditorConfigurableBlock
{
    abstract public static function variant(): string;

    abstract public static function defaultColumns(): int;

    public static function getId(): string
    {
        return 'site_footer_'.static::variant();
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::pro.editor.blocks.site_footer_'.static::variant());
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
        // Pass only variant-specific seeds + user config. Full defaults live inside
        // SiteFooterConfig::normalize so legacy show_brand=false is not overridden by
        // default show_logo_desktop=true from a pre-normalized defaultConfig() merge.
        $merged = SiteFooterConfig::normalize(array_merge(
            ['columns' => static::defaultColumns()],
            $config,
        ));

        $inner = view('voodbuilder::editor.blocks.footers.'.static::variant(), [
            'config' => $merged,
            'preview' => $preview,
        ])->render();

        $shell = EditorFooterBlockShell::compose(static::getId(), $merged, $inner);

        return EditorSlotHydrator::hydrateHtml($shell, $preview, $merged);
    }
}
