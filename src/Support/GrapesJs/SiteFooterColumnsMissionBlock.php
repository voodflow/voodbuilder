<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterColumnsMissionBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'columns_mission';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
