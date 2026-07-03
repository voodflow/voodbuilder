<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterSocialBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'social';
    }

    public static function defaultColumns(): int
    {
        return 1;
    }
}
