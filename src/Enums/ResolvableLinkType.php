<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Resolvable Link Type enumeration.
 */
enum ResolvableLinkType: string implements HasLabel
{
    case Page = 'page';
    case Route = 'route';
    case Url = 'url';
    case Mail = 'mail';

    public function getLabel(): string
    {
        return match ($this) {
            self::Page => __('Site page'),
            self::Route => __('App route'),
            self::Url => __('External URL'),
            self::Mail => __('Email'),
        };
    }
}
