<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuItemType: string implements HasLabel
{
    case Page = 'page';
    case Route = 'route';
    case Url = 'url';

    public function getLabel(): string
    {
        return match ($this) {
            self::Page => __('Site page'),
            self::Route => __('App route'),
            self::Url => __('External URL'),
        };
    }
}
