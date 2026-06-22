<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

final class SiteFooterEBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'e';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
