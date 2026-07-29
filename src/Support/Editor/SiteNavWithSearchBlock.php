<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class SiteNavWithSearchBlock extends AbstractSiteNavVariantBlock
{
    public static function variant(): string
    {
        return 'with_search';
    }
}
