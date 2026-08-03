<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageVisibility: string implements HasLabel
{
    case Public = 'public';
    case Registered = 'registered';
    case Subscriber = 'subscriber';

    public function getLabel(): string
    {
        return match ($this) {
            self::Public => __('voodbuilder::gate.visibility.public'),
            self::Registered => __('voodbuilder::gate.visibility.registered'),
            self::Subscriber => __('voodbuilder::gate.visibility.subscriber'),
        };
    }
}
