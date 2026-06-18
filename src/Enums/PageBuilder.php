<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Enums;

enum PageBuilder: string
{
    case RichEditor = 'rich_editor';
    case GrapesJs = 'grapesjs';

    public function label(): string
    {
        return match ($this) {
            self::RichEditor => __('vpress::pro.builders.rich_editor'),
            self::GrapesJs => __('vpress::pro.builders.grapesjs'),
        };
    }
}
