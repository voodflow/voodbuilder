<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

enum PopupAnalyticsEvent: string
{
    case Shown = 'shown';
    case Closed = 'closed';
    case CtaClick = 'cta_click';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
