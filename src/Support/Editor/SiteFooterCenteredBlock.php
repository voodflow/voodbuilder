<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class SiteFooterCenteredBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'centered';
    }

    public static function defaultColumns(): int
    {
        return 1;
    }
}
