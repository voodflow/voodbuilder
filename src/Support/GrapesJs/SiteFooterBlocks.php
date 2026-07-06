<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;

final class SiteFooterBlocks
{
    public static function isFooterBlockId(string $blockId): bool
    {
        return str_starts_with($blockId, 'site_footer_');
    }

    /**
     * @return list<class-string<GrapesJsServerBlock>>
     */
    public static function blockClasses(): array
    {
        return [
            SiteFooterColumnsSimpleBlock::class,
            SiteFooterColumnsNewsletterBlock::class,
            SiteFooterCenteredBlock::class,
            SiteFooterSocialBlock::class,
        ];
    }
}
