<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterColumnsNewsletterBelowBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'columns_newsletter_below';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
