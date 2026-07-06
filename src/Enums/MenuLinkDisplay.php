<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuLinkDisplay: string implements HasLabel
{
    case IconOnly = 'icon_only';
    case IconText = 'icon_text';
    case TextOnly = 'text_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::IconOnly => __('voodbuilder::admin.navigation.link_display_icon_only'),
            self::IconText => __('voodbuilder::admin.navigation.link_display_icon_text'),
            self::TextOnly => __('voodbuilder::admin.navigation.link_display_text_only'),
        };
    }

    public function showsIcon(): bool
    {
        return $this !== self::TextOnly;
    }

    public function showsLabel(): bool
    {
        return $this !== self::IconOnly;
    }
}
