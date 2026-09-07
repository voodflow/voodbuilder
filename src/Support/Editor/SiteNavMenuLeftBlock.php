<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Rich content / landing block: Site Nav Menu Left Block.
 */
final class SiteNavMenuLeftBlock extends AbstractSiteNavVariantBlock
{
    public static function variant(): string
    {
        return 'menu_left';
    }
}
