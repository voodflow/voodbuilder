<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterDBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'd';
    }

    public static function defaultColumns(): int
    {
        return 1;
    }
}
