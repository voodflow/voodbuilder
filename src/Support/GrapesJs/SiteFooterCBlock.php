<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

final class SiteFooterCBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'c';
    }

    public static function defaultColumns(): int
    {
        return 4;
    }
}
