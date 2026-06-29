<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterBBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'b';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
