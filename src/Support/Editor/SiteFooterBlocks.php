<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Contracts\EditorServerBlock;

/**
 * Site Footer Blocks.
 */
final class SiteFooterBlocks
{
    public static function isFooterBlockId(string $blockId): bool
    {
        return str_starts_with($blockId, 'site_footer_');
    }

    /**
     * @return list<class-string<EditorServerBlock>>
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
