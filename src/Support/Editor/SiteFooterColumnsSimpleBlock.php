<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Rich content / landing block: Site Footer Columns Simple Block.
 */
final class SiteFooterColumnsSimpleBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'columns_simple';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
