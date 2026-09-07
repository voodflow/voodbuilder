<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Menu Item Type enumeration.
 */
enum MenuItemType: string implements HasLabel
{
    case Group = 'group';
    case Page = 'page';
    case Route = 'route';
    case Url = 'url';
    case Mail = 'mail';

    public function getLabel(): string
    {
        return match ($this) {
            self::Group => __('Dropdown group'),
            self::Page => __('Site page'),
            self::Route => __('App route'),
            self::Url => __('External URL'),
            self::Mail => __('Email'),
        };
    }
}
