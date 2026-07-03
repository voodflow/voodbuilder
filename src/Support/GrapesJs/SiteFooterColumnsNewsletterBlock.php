<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterColumnsNewsletterBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'columns_newsletter';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
