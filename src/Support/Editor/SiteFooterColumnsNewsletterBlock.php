<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Rich content / landing block: Site Footer Columns Newsletter Block.
 */
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
