<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterABlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'a';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
