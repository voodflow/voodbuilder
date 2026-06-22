<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Contracts\GrapesJsServerBlock;

final class SiteHeaderGrapesJsBlock implements GrapesJsServerBlock
{
    public static function getId(): string
    {
        return 'site_header';
    }

    public static function getLabel(): string
    {
        return __('vpress::pro.grapesjs.blocks.site_header');
    }

    public static function defaultConfig(): array
    {
        return [
            'main_menu' => 'main',
            'extra_menu' => 'header_extra',
        ];
    }

    public static function toHtml(array $config, array $context): string
    {
        return view('vpress::grapesjs.blocks.site-header', [
            'config' => $config,
            'preview' => false,
        ])->render();
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return view('vpress::grapesjs.blocks.site-header', [
            'config' => $config,
            'preview' => true,
        ])->render();
    }
}
