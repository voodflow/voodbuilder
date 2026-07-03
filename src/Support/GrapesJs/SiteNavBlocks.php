<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;

final class SiteNavBlocks
{
    public static function isNavBlockId(string $blockId): bool
    {
        return str_starts_with($blockId, 'site_nav_');
    }

    /**
     * @return list<class-string<GrapesJsServerBlock>>
     */
    public static function blockClasses(): array
    {
        return [
            SiteNavSimpleBlock::class,
            SiteNavSimpleDarkBlock::class,
            SiteNavWithSearchBlock::class,
            SiteNavWithSearchDarkBlock::class,
            SiteNavWithActionBlock::class,
            SiteNavWithActionDarkBlock::class,
            SiteNavCenteredLinksBlock::class,
            SiteNavMenuLeftBlock::class,
        ];
    }
}
