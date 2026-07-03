<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterColumnsBrandEndBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'columns_brand_end';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
