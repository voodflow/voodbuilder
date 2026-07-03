<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterColumnsCtaBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'columns_cta';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
