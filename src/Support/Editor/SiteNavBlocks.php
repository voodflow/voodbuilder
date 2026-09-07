<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Contracts\EditorServerBlock;

/**
 * Site Nav Blocks.
 */
final class SiteNavBlocks
{
    public static function isNavBlockId(string $blockId): bool
    {
        return str_starts_with($blockId, 'site_nav_');
    }

    /**
     * @return list<class-string<EditorServerBlock>>
     */
    public static function blockClasses(): array
    {
        return [
            SiteNavSimpleBlock::class,
        ];
    }
}
