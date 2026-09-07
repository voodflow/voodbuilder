<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Rich content / landing block: Site Footer Social Block.
 */
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
