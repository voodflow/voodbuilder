<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Desktop dropdown panel layout for a parent menu item with children.
 */
enum MenuDropdownLayout: string implements HasLabel
{
    case Auto = 'auto';
    case List = 'list';
    case Mega = 'mega';

    public function getLabel(): string
    {
        return match ($this) {
            self::Auto => __('voodbuilder::admin.navigation.dropdown_layout_auto'),
            self::List => __('voodbuilder::admin.navigation.dropdown_layout_list'),
            self::Mega => __('voodbuilder::admin.navigation.dropdown_layout_mega'),
        };
    }
}
