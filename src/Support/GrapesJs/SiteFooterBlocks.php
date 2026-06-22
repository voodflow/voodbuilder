<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Contracts\GrapesJsServerBlock;

final class SiteFooterBlocks
{
    public static function isFooterBlockId(string $blockId): bool
    {
        return $blockId === 'site_footer' || str_starts_with($blockId, 'site_footer_');
    }

    /**
     * @return list<class-string<GrapesJsServerBlock>>
     */
    public static function blockClasses(): array
    {
        return [
            SiteFooterABlock::class,
            SiteFooterBBlock::class,
            SiteFooterCBlock::class,
            SiteFooterDBlock::class,
            SiteFooterEBlock::class,
        ];
    }
}
